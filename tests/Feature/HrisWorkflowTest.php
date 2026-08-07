<?php

use App\Actions\Attendance\RecordAttendance;
use App\Actions\Leave\ReviewLeaveRequest;
use App\Actions\Leave\SubmitLeaveRequest;
use App\Actions\Payroll\GeneratePayrollPeriod;
use App\Enums\LeaveRequestStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

function hrisEmployee(UserRole $role = UserRole::Employee): Employee
{
    $department = Department::create(['name' => fake()->unique()->company(), 'code' => fake()->unique()->lexify('???')]);
    $position = Position::create(['department_id' => $department->id, 'name' => fake()->jobTitle()]);
    $user = User::factory()->create(['role' => $role]);

    return Employee::create([
        'user_id' => $user->id,
        'employee_number' => fake()->unique()->numerify('NSH-####'),
        'department_id' => $department->id,
        'position_id' => $position->id,
        'joined_at' => now()->subYear(),
        'basic_salary' => 8_000_000,
    ]);
}

test('leave submission reserves balance and approval consumes it', function () {
    Date::setTestNow('2026-08-03 08:00:00');
    $employee = hrisEmployee();
    $reviewer = User::factory()->create(['role' => UserRole::HrAdmin]);
    $type = LeaveType::create(['name' => 'Annual', 'annual_quota' => 12]);
    $balance = LeaveBalance::create(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => 2026, 'entitled' => 12]);

    $request = app(SubmitLeaveRequest::class)->handle($employee, [
        'leave_type_id' => $type->id,
        'start_date' => '2026-08-10',
        'end_date' => '2026-08-11',
        'reason' => 'Family event',
    ]);

    expect((float) $balance->refresh()->pending)->toBe(2.0);

    app(ReviewLeaveRequest::class)->handle($request, $reviewer, LeaveRequestStatus::Approved);

    expect((float) $balance->refresh()->pending)->toBe(0.0)
        ->and((float) $balance->used)->toBe(2.0);
});

test('attendance cannot be checked in twice', function () {
    Date::setTestNow('2026-08-03 08:05:00');
    $employee = hrisEmployee();
    $action = app(RecordAttendance::class);

    $action->checkIn($employee);
    $action->checkIn($employee);
})->throws(ValidationException::class);

test('payroll generation snapshots every active employee exactly once', function () {
    hrisEmployee();
    hrisEmployee();

    $period = app(GeneratePayrollPeriod::class)->handle([
        'name' => 'August 2026',
        'starts_on' => '2026-08-01',
        'ends_on' => '2026-08-31',
    ]);

    expect($period->records)->toHaveCount(2)
        ->and($period->records->pluck('net_salary')->unique()->all())->toBe(['8000000.00']);
});

test('employees cannot access HR employee management', function () {
    $employee = hrisEmployee();

    $this->actingAs($employee->user)->get(route('employees.index'))->assertForbidden();
});
