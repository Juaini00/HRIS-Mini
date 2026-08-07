<?php

namespace App\Http\Controllers\Hris;

use App\Actions\Audit\WriteAuditLog;
use App\Actions\Leave\CancelLeaveRequest;
use App\Actions\Leave\ReviewLeaveRequest;
use App\Actions\Leave\SubmitLeaveRequest;
use App\Enums\LeaveRequestStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        $employeeId = $employee?->id;
        $query = LeaveRequest::query()->with(['employee.user', 'leaveType'])->latest();
        if (! $request->user()->isAdministrator()) {
            $request->user()->role === UserRole::Manager
                ? $query->where(fn ($builder) => $builder->where('employee_id', $employeeId)->orWhereHas('employee', fn ($employees) => $employees->where('manager_id', $employeeId)))
                : $query->where('employee_id', $employeeId ?? 0);
        }
        $calendar = $this->calendar($request, $employee);

        return Inertia::render('hris/leave', [
            'requests' => $query->paginate(20)->withQueryString(),
            'types' => LeaveType::query()->where('is_active', true)->orderBy('name')->get(),
            'balances' => $employee ? LeaveBalance::query()->with('leaveType')->where('employee_id', $employee->id)->where('year', now()->year)->get() : [],
            'canReview' => $request->user()->isAdministrator() || $request->user()->role === UserRole::Manager,
            ...$calendar,
        ]);
    }

    /**
     * Data for the leave calendar.
     *
     * The scope a user may request is derived from their role, never taken at face value
     * from the query string: an employee asking for `company` is silently served their own
     * calendar rather than being shown everyone's leave.
     *
     * @return array<string, mixed>
     */
    private function calendar(Request $request, ?Employee $employee): array
    {
        $user = $request->user();
        $isHr = $user->isAdministrator();
        $isManager = $user->role === UserRole::Manager;

        $allowedScopes = match (true) {
            $isHr => ['personal', 'team', 'company'],
            $isManager => ['personal', 'team'],
            default => ['personal'],
        };

        $scope = in_array($request->query('scope'), $allowedScopes, true)
            ? (string) $request->query('scope')
            : ($isHr ? 'company' : ($isManager ? 'team' : 'personal'));

        $month = CarbonImmutable::parse($request->date('month') ?? today())->startOfMonth();
        $windowStart = $month->startOfMonth()->toDateString();
        $windowEnd = $month->endOfMonth()->toDateString();

        $query = LeaveRequest::query()
            ->where('status', LeaveRequestStatus::Approved)
            // Any request overlapping the month, not only ones starting inside it.
            ->where('start_date', '<=', $windowEnd)
            ->where('end_date', '>=', $windowStart)
            ->with(['employee:id,user_id,employee_number,department_id', 'employee.user:id,name', 'leaveType:id,name,color']);

        // 0 is a deliberate never-matching id: a user without an employee record sees an
        // empty calendar rather than everyone's.
        $ownId = $employee->id ?? 0;

        match ($scope) {
            'company' => null,
            'team' => $query->whereIn('employee_id', [...($employee?->descendantIds() ?? []), $ownId]),
            default => $query->where('employee_id', $ownId),
        };

        if ($isHr && $request->filled('department_id')) {
            $query->whereHas('employee', fn (Builder $q) => $q->where('department_id', $request->integer('department_id')));
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->integer('leave_type_id'));
        }

        return [
            // Explicit column list, not `get()`: a leave reason is confidential and must
            // never reach the browser just because the calendar needed the dates.
            'calendar' => $query->orderBy('start_date')->get([
                'id', 'employee_id', 'leave_type_id', 'start_date', 'end_date',
                'start_session', 'end_session', 'days', 'status',
            ]),
            'calendarScope' => $scope,
            'calendarScopes' => $allowedScopes,
            'calendarMonth' => $month->toDateString(),
            'holidays' => Holiday::query()
                ->where('is_active', true)
                ->whereBetween('date', [$windowStart, $windowEnd])
                ->get(['id', 'date', 'name']),
            'departments' => $isHr
                ? Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                : [],
            'filters' => [
                'department_id' => $request->query('department_id'),
                'leave_type_id' => $request->query('leave_type_id'),
            ],
        ];
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
        $data = $request->validate(['status' => ['required', Rule::in([LeaveRequestStatus::Approved->value, LeaveRequestStatus::Rejected->value])], 'notes' => ['nullable', 'string', 'max:1000']]);
        $review->handle($leaveRequest, $request->user(), LeaveRequestStatus::from($data['status']), $data['notes'] ?? null);
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
        $path = $leaveRequest->attachment_path;
        abort_unless($path !== null, 404);

        return Storage::disk('local')->download($path);
    }

    public function adjustBalance(Request $request, WriteAuditLog $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403);
        $data = $request->validate(['employee_id' => ['required', 'exists:employees,id'], 'leave_type_id' => ['required', 'exists:leave_types,id'], 'year' => ['required', 'integer', 'min:2020', 'max:2100'], 'entitled' => ['required', 'numeric', 'min:0', 'max:365']]);
        $balance = LeaveBalance::updateOrCreate(Arr::only($data, ['employee_id', 'leave_type_id', 'year']), ['entitled' => $data['entitled']]);
        $audit->handle($request, 'leave-balance.adjusted', $balance, ['entitled' => $data['entitled']]);

        return back()->with('success', 'Saldo cuti disesuaikan.');
    }
}
