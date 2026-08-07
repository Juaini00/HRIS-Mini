<?php

use App\Actions\Leave\CancelLeaveRequest;
use App\Actions\Leave\ReviewLeaveRequest;
use App\Actions\Leave\SubmitLeaveRequest;
use App\Enums\LeaveRequestStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Notifications\LeaveReviewedNotification;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

function leaveLifecycleEmployee(UserRole $role = UserRole::Employee): Employee
{
    $department = Department::create(['name' => fake()->unique()->company(), 'code' => fake()->unique()->lexify('???')]);
    $position = Position::create(['department_id' => $department->id, 'name' => fake()->jobTitle()]);
    $user = User::factory()->create(['role' => $role]);

    return Employee::create(['user_id' => $user->id, 'employee_number' => fake()->unique()->numerify('LEV-####'), 'department_id' => $department->id, 'position_id' => $position->id, 'joined_at' => '2025-01-01', 'basic_salary' => 1]);
}

function leaveLifecycleBalance(Employee $employee): array
{
    $type = LeaveType::create(['name' => fake()->unique()->word(), 'annual_quota' => 12]);
    $balance = LeaveBalance::create(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => 2026, 'entitled' => 12]);

    return [$type, $balance];
}

test('half day leave reserves half a day and cancellation restores it', function () {
    Date::setTestNow('2026-08-07 08:00:00');
    $employee = leaveLifecycleEmployee();
    [$type, $balance] = leaveLifecycleBalance($employee);
    $leave = app(SubmitLeaveRequest::class)->handle($employee, ['leave_type_id' => $type->id, 'start_date' => '2026-08-10', 'end_date' => '2026-08-10', 'duration_type' => 'first_half', 'reason' => 'Appointment']);

    expect((float) $leave->days)->toBe(0.5)->and((float) $balance->refresh()->pending)->toBe(0.5);
    app(CancelLeaveRequest::class)->handle($leave, $employee->user, 'No longer needed');
    expect((float) $balance->refresh()->pending)->toBe(0.0)->and($leave->refresh()->status)->toBe(LeaveRequestStatus::Cancelled);
});

test('cancelling an approved future leave restores used balance', function () {
    Notification::fake();
    Date::setTestNow('2026-08-07 08:00:00');
    $employee = leaveLifecycleEmployee();
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    [$type, $balance] = leaveLifecycleBalance($employee);
    $leave = app(SubmitLeaveRequest::class)->handle($employee, ['leave_type_id' => $type->id, 'start_date' => '2026-08-10', 'end_date' => '2026-08-11', 'reason' => 'Rest']);
    app(ReviewLeaveRequest::class)->handle($leave, $hr, LeaveRequestStatus::Approved);
    expect((float) $balance->refresh()->used)->toBe(2.0);
    app(CancelLeaveRequest::class)->handle($leave, $employee->user, 'Changed plan');
    expect((float) $balance->refresh()->used)->toBe(0.0);
});

test('review dispatches notification and manager cannot approve own request', function () {
    Notification::fake();
    Date::setTestNow('2026-08-07 08:00:00');
    $manager = leaveLifecycleEmployee(UserRole::Manager);
    [$type] = leaveLifecycleBalance($manager);
    $leave = app(SubmitLeaveRequest::class)->handle($manager, ['leave_type_id' => $type->id, 'start_date' => '2026-08-10', 'end_date' => '2026-08-10', 'reason' => 'Rest']);
    $this->actingAs($manager->user)->patch(route('leave.review', $leave), ['status' => 'approved'])->assertForbidden();

    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    app(ReviewLeaveRequest::class)->handle($leave, $hr, LeaveRequestStatus::Approved);
    Notification::assertSentTo($manager->user, LeaveReviewedNotification::class);
    expect(fn () => app(ReviewLeaveRequest::class)->handle($leave, $hr, LeaveRequestStatus::Approved))
        ->toThrow(ValidationException::class);
});

test('unpaid leave does not require or mutate paid leave balance', function () {
    Date::setTestNow('2026-08-07 08:00:00');
    $employee = leaveLifecycleEmployee();
    $type = LeaveType::create(['name' => 'Unpaid', 'annual_quota' => 0, 'is_paid' => false]);
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);

    $leave = app(SubmitLeaveRequest::class)->handle($employee, ['leave_type_id' => $type->id, 'start_date' => '2026-08-10', 'end_date' => '2026-08-10', 'reason' => 'Personal']);
    app(ReviewLeaveRequest::class)->handle($leave, $hr, LeaveRequestStatus::Approved);

    expect($leave->refresh()->status)->toBe(LeaveRequestStatus::Approved)
        ->and(LeaveBalance::where('employee_id', $employee->id)->exists())->toBeFalse();
});
