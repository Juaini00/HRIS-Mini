<?php

use App\Actions\Employees\CreateEmployee;
use App\Actions\Employees\GenerateEmployeeNumber;
use App\Enums\EmploymentStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;

function employeePayload(array $overrides = []): array
{
    $department = Department::factory()->create();
    $position = Position::factory()->create(['department_id' => $department->id]);

    return [
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'department_id' => $department->id,
        'position_id' => $position->id,
        'joined_at' => '2026-03-01',
        'basic_salary' => 9_000_000,
        'role' => UserRole::Employee,
        ...$overrides,
    ];
}

test('employee numbers are generated per year in a readable sequence', function () {
    $generator = app(GenerateEmployeeNumber::class);

    expect($generator->next(2026))->toBe('EMP-2026-0001');

    Employee::factory()->create(['employee_number' => 'EMP-2026-0001']);
    expect($generator->next(2026))->toBe('EMP-2026-0002');

    Employee::factory()->create(['employee_number' => 'EMP-2026-0009']);
    expect($generator->next(2026))->toBe('EMP-2026-0010');
});

test('each year restarts its own sequence', function () {
    Employee::factory()->create(['employee_number' => 'EMP-2025-0042']);

    expect(app(GenerateEmployeeNumber::class)->next(2026))->toBe('EMP-2026-0001')
        ->and(app(GenerateEmployeeNumber::class)->next(2025))->toBe('EMP-2025-0043');
});

test('creating an employee provisions the user and salary history transactionally', function () {
    $actor = User::factory()->create(['role' => UserRole::HrAdmin]);

    $employee = app(CreateEmployee::class)->handle(employeePayload(), $actor);

    expect($employee->employee_number)->toBe('EMP-2026-0001')
        ->and($employee->employment_status)->toBe(EmploymentStatus::Active)
        ->and($employee->created_by)->toBe($actor->id)
        ->and($employee->user->role)->toBe(UserRole::Employee)
        ->and($employee->user->is_active)->toBeTrue()
        ->and($employee->salaryHistories()->count())->toBe(1)
        ->and((float) $employee->salaryHistories()->value('amount'))->toBe(9_000_000.0);
});

test('a failed employee creation leaves no orphan user behind', function () {
    $actor = User::factory()->create(['role' => UserRole::HrAdmin]);
    $usersBefore = User::count();

    // A non-existent department trips the foreign key inside the transaction.
    expect(fn () => app(CreateEmployee::class)->handle(
        employeePayload(['department_id' => 999_999]),
        $actor,
    ))->toThrow(QueryException::class);

    expect(User::count())->toBe($usersBefore)
        ->and(Employee::count())->toBe(0);
});

test('an explicitly supplied employee number is honoured and must stay unique', function () {
    $actor = User::factory()->create(['role' => UserRole::HrAdmin]);

    $employee = app(CreateEmployee::class)->handle(
        employeePayload(['employee_number' => 'LEGACY-007']),
        $actor,
    );
    expect($employee->employee_number)->toBe('LEGACY-007');

    expect(fn () => app(CreateEmployee::class)->handle(
        employeePayload(['employee_number' => 'LEGACY-007']),
        $actor,
    ))->toThrow(UniqueConstraintViolationException::class);
});

test('an employee cannot manage themselves or close a reporting loop', function () {
    $top = Employee::factory()->create();
    $middle = Employee::factory()->create(['manager_id' => $top->id]);
    $bottom = Employee::factory()->create(['manager_id' => $middle->id]);

    expect($top->wouldCreateReportingCycle($top->id))->toBeTrue()
        ->and($top->wouldCreateReportingCycle($bottom->id))->toBeTrue()
        ->and($top->wouldCreateReportingCycle($middle->id))->toBeTrue()
        ->and($bottom->wouldCreateReportingCycle($top->id))->toBeFalse()
        ->and($bottom->wouldCreateReportingCycle(null))->toBeFalse()
        ->and($top->descendantIds())->toEqualCanonicalizing([$middle->id, $bottom->id]);
});
