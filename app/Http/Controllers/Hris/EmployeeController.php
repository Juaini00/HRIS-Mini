<?php

namespace App\Http\Controllers\Hris;

use App\Actions\Audit\WriteAuditLog;
use App\Actions\Employees\CreateEmployee;
use App\Enums\PayrollPeriodStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\DeactivateEmployeeRequest;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Location;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Employee::class);
        $query = Employee::query()->with(['user', 'department', 'position', 'manager.user'])->latest();
        if ($request->user()->role === UserRole::Manager) {
            $query->where('manager_id', $request->user()->employee?->id);
        }

        return Inertia::render('hris/employees', [
            'employees' => $query->paginate(20)->withQueryString(),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(),
            'positions' => Position::query()->where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(),
            'employmentTypes' => EmploymentType::query()->where('is_active', true)->orderBy('name')->get(),
            'managers' => Employee::query()->with('user:id,name')->whereNull('ended_at')->orderBy('employee_number')->get(['id', 'user_id', 'employee_number']),
            'canCreate' => $request->user()->can('create', Employee::class),
        ]);
    }

    public function show(Request $request, Employee $employee): Response
    {
        Gate::authorize('view', $employee);
        $employee->load(['user', 'department', 'position', 'location', 'employmentType', 'manager.user', 'documents.uploader:id,name', 'reports.user:id,name']);

        $canSeeSensitive = $request->user()->can('viewSensitive', $employee);

        if ($canSeeSensitive) {
            $employee->load('salaryHistories');
            $employee->makeVisible([
                'basic_salary', 'bank_account', 'bank_name', 'bank_account_holder',
                'tax_number', 'personal_email', 'address', 'city', 'province',
                'postal_code', 'emergency_contact_name', 'emergency_contact_relationship',
                'emergency_contact_phone', 'notes',
            ]);
        }

        return Inertia::render('hris/employee-detail', [
            'employee' => $employee,
            'canUpdate' => $request->user()->can('update', $employee),
            'canSeeSensitive' => $canSeeSensitive,
            'summaries' => $this->summaries($employee, $canSeeSensitive),
            'timeline' => $this->timeline($employee),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'positions' => Position::where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'employmentTypes' => EmploymentType::where('is_active', true)->orderBy('name')->get(),
            'managers' => Employee::with('user:id,name')->whereNull('ended_at')->whereKeyNot($employee->id)->get(['id', 'user_id']),
        ]);
    }

    /**
     * Attendance, leave, and payroll roll-ups for the detail tabs.
     *
     * Payroll figures are gated on the same permission as the rest of the compensation
     * data, so a manager viewing a report gets the attendance and leave tabs without the
     * money.
     *
     * @return array<string, mixed>
     */
    private function summaries(Employee $employee, bool $canSeeSensitive): array
    {
        $monthStart = today()->startOfMonth()->toDateString();

        return [
            'attendance' => [
                'thisMonth' => DB::table('attendances')
                    ->where('employee_id', $employee->id)
                    ->where('date', '>=', $monthStart)
                    ->groupBy('status')
                    ->selectRaw('count(*) as total')
                    ->pluck('total', 'status'),
                'recent' => $employee->attendances()
                    ->orderByDesc('date')
                    ->limit(10)
                    ->get(['id', 'date', 'status', 'checked_in_at', 'checked_out_at', 'worked_minutes', 'late_minutes']),
            ],
            'leave' => [
                'balances' => $employee->leaveBalances()
                    ->where('year', now()->year)
                    ->with('leaveType:id,name,color')
                    ->get(),
                'recent' => $employee->leaveRequests()
                    ->with('leaveType:id,name,color')
                    ->orderByDesc('start_date')
                    ->limit(10)
                    ->get(['id', 'leave_type_id', 'request_number', 'start_date', 'end_date', 'days', 'status']),
            ],
            'payroll' => $canSeeSensitive
                ? $employee->payrollRecords()
                    ->whereHas('period', fn (Builder $q) => $q->whereIn('status', [PayrollPeriodStatus::Published, PayrollPeriodStatus::Closed]))
                    ->with('period:id,name,payment_date,status')
                    ->orderByDesc('id')
                    ->limit(12)
                    ->get(['id', 'payroll_period_id', 'basic_salary', 'earnings', 'deductions', 'net_salary'])
                : [],
        ];
    }

    /**
     * Recent audited activity for this employee.
     *
     * @return array<int, mixed>
     */
    private function timeline(Employee $employee): array
    {
        return AuditLog::query()
            ->where('auditable_type', $employee->getMorphClass())
            ->where('auditable_id', $employee->id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'user_id', 'event', 'event_category', 'description', 'created_at'])
            ->all();
    }

    /**
     * Replace the employee's profile photo.
     */
    public function updatePhoto(Request $request, Employee $employee, WriteAuditLog $audit): RedirectResponse
    {
        Gate::authorize('update', $employee);

        $request->validate([
            // MIME type is checked from the file contents, not the supplied extension.
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=100,min_height=100'],
        ]);

        $previous = $employee->photo_path;
        $path = $request->file('photo')->store('employee-photos', 'public');

        $employee->update(['photo_path' => $path, 'updated_by' => $request->user()->id]);

        if ($previous !== null) {
            Storage::disk('public')->delete($previous);
        }

        $audit->handle($request, 'employee.photo-updated', $employee);

        return back()->with('success', 'Foto profil diperbarui.');
    }

    public function store(StoreEmployeeRequest $request, CreateEmployee $createEmployee): RedirectResponse
    {
        // The action raises EmployeeCreated; the audit listener records it.
        $createEmployee->handle($request->validated(), $request->user());

        return back()->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee, WriteAuditLog $audit): RedirectResponse
    {
        $data = $request->validated();
        $before = $employee->only(['department_id', 'position_id', 'location_id', 'manager_id', 'phone', 'basic_salary']);
        DB::transaction(function () use ($employee, $data, $request): void {
            $salaryChanged = bccomp(number_format((float) $employee->basic_salary, 2, '.', ''), number_format((float) $data['basic_salary'], 2, '.', ''), 2) !== 0;
            $employee->user->update(['name' => $data['name'], 'email' => $data['email'], 'role' => $data['role']]);
            $employee->update(collect($data)->except(['name', 'email', 'role'])->all());
            if ($salaryChanged) {
                $employee->salaryHistories()->whereNull('effective_to')->update(['effective_to' => today()->subDay()]);
                $employee->salaryHistories()->create(['amount' => $data['basic_salary'], 'effective_from' => today(), 'created_by' => $request->user()->id, 'notes' => 'Profile update']);
            }
        });
        $audit->handle($request, 'employee.updated', $employee, ['before' => $before, 'after' => $employee->fresh()->only(array_keys($before))]);

        return back()->with('success', 'Data karyawan diperbarui.');
    }

    public function deactivate(DeactivateEmployeeRequest $request, Employee $employee, WriteAuditLog $audit): RedirectResponse
    {
        DB::transaction(function () use ($employee, $request): void {
            $employee->update(['ended_at' => $request->date('ended_at')]);
            $employee->user->update(['is_active' => false]);
            $employee->reports()->update(['manager_id' => null]);
        });
        $audit->handle($request, 'employee.deactivated', $employee, ['reason' => $request->string('reason')->toString(), 'ended_at' => $request->input('ended_at')]);

        return back()->with('success', 'Karyawan dinonaktifkan.');
    }
}
