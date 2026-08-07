<?php

namespace App\Http\Controllers\Hris;

use App\Actions\Audit\WriteAuditLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\DeactivateEmployeeRequest;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\Location;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Employee::class);
        $query = Employee::query()->with(['user', 'department', 'position', 'manager.user'])->latest();
        if ($request->user()->role === \App\Enums\UserRole::Manager) {
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
        $employee->load(['user', 'department', 'position', 'manager.user', 'documents', 'salaryHistories']);
        if (! $request->user()->can('viewSensitive', $employee)) {
            $employee->makeHidden(['basic_salary', 'bank_account'])->unsetRelation('salaryHistories');
        } else {
            $employee->makeVisible(['basic_salary', 'bank_account']);
        }

        return Inertia::render('hris/employee-detail', [
            'employee' => $employee,
            'canUpdate' => $request->user()->can('update', $employee),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'positions' => Position::where('is_active', true)->orderBy('name')->get(),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'employmentTypes' => EmploymentType::where('is_active', true)->orderBy('name')->get(),
            'managers' => Employee::with('user:id,name')->whereNull('ended_at')->whereKeyNot($employee->id)->get(['id', 'user_id']),
        ]);
    }

    public function store(StoreEmployeeRequest $request, WriteAuditLog $audit): RedirectResponse
    {
        $data = $request->validated();
        $employee = DB::transaction(function () use ($data, $request): Employee {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make('NusaHR123!'), 'role' => $data['role'], 'email_verified_at' => now()]);
            $employee = $user->employee()->create([...collect($data)->except(['name', 'email', 'role'])->all(), 'employee_number' => ($data['employee_number'] ?? null) ?: sprintf('NSH-%05d', $user->id)]);
            $employee->salaryHistories()->create(['amount' => $data['basic_salary'], 'effective_from' => $data['joined_at'], 'created_by' => $request->user()->id, 'notes' => 'Initial salary']);

            return $employee;
        });
        $audit->handle($request, 'employee.created', $employee, ['employee_number' => $employee->employee_number]);

        return back()->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee, WriteAuditLog $audit): RedirectResponse
    {
        $data = $request->validated();
        $before = $employee->only(['department_id', 'position_id', 'location_id', 'manager_id', 'phone', 'basic_salary']);
        DB::transaction(function () use ($employee, $data, $request): void {
            $salaryChanged = bccomp((string) $employee->basic_salary, (string) $data['basic_salary'], 2) !== 0;
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
