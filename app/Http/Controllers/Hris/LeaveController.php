<?php

namespace App\Http\Controllers\Hris;

use App\Actions\Audit\WriteAuditLog;
use App\Actions\Leave\CancelLeaveRequest;
use App\Actions\Leave\ReviewLeaveRequest;
use App\Actions\Leave\SubmitLeaveRequest;
use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveController extends Controller
{
    public function index(Request $request): Response
    {
        $employee = $request->user()->employee;
        $query = LeaveRequest::query()->with(['employee.user', 'leaveType'])->latest();
        if (! $request->user()->isAdministrator()) {
            $request->user()->role === UserRole::Manager
                ? $query->where(fn ($builder) => $builder->where('employee_id', $employee?->id)->orWhereHas('employee', fn ($employees) => $employees->where('manager_id', $employee?->id)))
                : $query->where('employee_id', $employee?->id ?? 0);
        }
        $calendar = LeaveRequest::query()->with(['employee.user', 'leaveType'])->where('status', LeaveStatus::Approved)->whereBetween('start_date', [today()->startOfMonth(), today()->addMonths(3)->endOfMonth()]);
        if (! $request->user()->isAdministrator()) {
            $request->user()->role === UserRole::Manager
                ? $calendar->where(fn ($builder) => $builder->where('employee_id', $employee?->id)->orWhereHas('employee', fn ($employees) => $employees->where('manager_id', $employee?->id)))
                : $calendar->where('employee_id', $employee?->id ?? 0);
        }

        return Inertia::render('hris/leave', [
            'requests' => $query->paginate(20)->withQueryString(),
            'types' => LeaveType::query()->where('is_active', true)->orderBy('name')->get(),
            'balances' => $employee ? LeaveBalance::query()->with('leaveType')->where('employee_id', $employee->id)->where('year', now()->year)->get() : [],
            'calendar' => $calendar->orderBy('start_date')->get(),
            'canReview' => $request->user()->isAdministrator() || $request->user()->role === UserRole::Manager,
        ]);
    }

    public function store(StoreLeaveRequest $request, SubmitLeaveRequest $submit, WriteAuditLog $audit): RedirectResponse
    {
        $leaveRequest = $submit->handle($request->user()->employee, $request->safe()->except('attachment'), $request->file('attachment'));
        $audit->handle($request, 'leave.submitted', $leaveRequest, ['days' => $leaveRequest->days, 'leave_type_id' => $leaveRequest->leave_type_id]);

        return back()->with('success', 'Permintaan cuti dikirim.');
    }

    public function review(Request $request, LeaveRequest $leaveRequest, ReviewLeaveRequest $review, WriteAuditLog $audit): RedirectResponse
    {
        Gate::authorize('review', $leaveRequest);
        $data = $request->validate(['status' => ['required', Rule::in([LeaveStatus::Approved->value, LeaveStatus::Rejected->value])], 'notes' => ['nullable', 'string', 'max:1000']]);
        $review->handle($leaveRequest, $request->user(), LeaveStatus::from($data['status']), $data['notes'] ?? null);
        $audit->handle($request, "leave.{$data['status']}", $leaveRequest, ['notes' => $data['notes'] ?? null]);

        return back()->with('success', 'Permintaan cuti diproses.');
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest, CancelLeaveRequest $cancel, WriteAuditLog $audit): RedirectResponse
    {
        Gate::authorize('cancel', $leaveRequest);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $cancel->handle($leaveRequest, $request->user(), $data['reason']);
        $audit->handle($request, 'leave.cancelled', $leaveRequest, ['reason' => $data['reason']]);

        return back()->with('success', 'Cuti dibatalkan dan saldo dipulihkan.');
    }

    public function attachment(Request $request, LeaveRequest $leaveRequest): StreamedResponse
    {
        Gate::authorize('view', $leaveRequest);
        abort_unless($leaveRequest->attachment_path, 404);

        return Storage::disk('local')->download($leaveRequest->attachment_path);
    }

    public function adjustBalance(Request $request, WriteAuditLog $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403);
        $data = $request->validate(['employee_id' => ['required', 'exists:employees,id'], 'leave_type_id' => ['required', 'exists:leave_types,id'], 'year' => ['required', 'integer', 'min:2020', 'max:2100'], 'entitled' => ['required', 'numeric', 'min:0', 'max:365']]);
        $balance = LeaveBalance::updateOrCreate(collect($data)->only(['employee_id', 'leave_type_id', 'year'])->all(), ['entitled' => $data['entitled']]);
        $audit->handle($request, 'leave-balance.adjusted', $balance, ['entitled' => $data['entitled']]);

        return back()->with('success', 'Saldo cuti disesuaikan.');
    }
}
