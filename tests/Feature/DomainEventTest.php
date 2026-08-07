<?php

use App\Actions\Attendance\CorrectAttendance;
use App\Actions\Employees\CreateEmployee;
use App\Actions\Leave\ReviewLeaveRequest;
use App\Actions\Leave\SubmitLeaveRequest;
use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\UserRole;
use App\Events\AttendanceCorrected;
use App\Events\EmployeeCreated;
use App\Events\LeaveRequestReviewed;
use App\Events\LeaveRequestSubmitted;
use App\Listeners\RecordDomainAudit;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Notifications\LeaveReviewedNotification;
use App\Notifications\LeaveSubmittedNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

function leaveFixture(): array
{
    $manager = Employee::factory()->create();
    $employee = Employee::factory()->create(['manager_id' => $manager->id]);
    $type = LeaveType::factory()->create(['is_paid' => true, 'annual_quota' => 12]);

    LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $type->id,
        'year' => (int) now()->year,
        'entitled' => 12,
    ]);

    return [$employee, $type, $manager];
}

it('raises EmployeeCreated when an employee is provisioned', function () {
    Event::fake([EmployeeCreated::class]);
    $actor = User::factory()->create(['role' => UserRole::HrAdmin]);
    $department = Department::factory()->create();
    $position = Position::factory()->create(['department_id' => $department->id]);

    $employee = app(CreateEmployee::class)->handle([
        'name' => 'Sinta Dewi',
        'email' => 'sinta@nusahr.test',
        'department_id' => $department->id,
        'position_id' => $position->id,
        'joined_at' => '2026-02-01',
        'basic_salary' => 9_000_000,
        'role' => UserRole::Employee,
    ], $actor);

    Event::assertDispatched(
        EmployeeCreated::class,
        fn (EmployeeCreated $e) => $e->employee->is($employee) && $e->actor->is($actor),
    );
});

it('raises LeaveRequestSubmitted and notifies the approvers', function () {
    Notification::fake();
    [$employee, $type, $manager] = leaveFixture();
    $hr = User::factory()->create(['role' => UserRole::HrAdmin, 'is_active' => true]);

    app(SubmitLeaveRequest::class)->handle($employee, [
        'leave_type_id' => $type->id,
        'start_date' => today()->addDays(10)->toDateString(),
        'end_date' => today()->addDays(10)->toDateString(),
        'reason' => 'Family matter',
    ]);

    Notification::assertSentTo($manager->user, LeaveSubmittedNotification::class);
    Notification::assertSentTo($hr, LeaveSubmittedNotification::class);
});

it('writes an audit entry through the listener rather than the controller', function () {
    Notification::fake();
    [$employee, $type, $manager] = leaveFixture();

    $request = app(SubmitLeaveRequest::class)->handle($employee, [
        'leave_type_id' => $type->id,
        'start_date' => today()->addDays(10)->toDateString(),
        'end_date' => today()->addDays(10)->toDateString(),
        'reason' => 'Family matter',
    ]);

    expect(AuditLog::where('event', 'leave.submitted')->exists())->toBeTrue();

    app(ReviewLeaveRequest::class)->handle($request, $manager->user, LeaveRequestStatus::Approved);

    $approved = AuditLog::where('event', 'leave.approved')->firstOrFail();
    expect($approved->event_category)->toBe('leave')
        ->and($approved->auditable_id)->toBe($request->id)
        ->and($approved->new_values['status'])->toBe(LeaveRequestStatus::Approved->value);

    Notification::assertSentTo($employee->user, LeaveReviewedNotification::class);
});

it('records the before and after values when HR corrects attendance', function () {
    $employee = Employee::factory()->create();
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $attendance = Attendance::create([
        'employee_id' => $employee->id,
        'date' => '2026-08-03',
        'checked_in_at' => '2026-08-03 09:30:00',
        'late_minutes' => 30,
        'status' => AttendanceStatus::Late,
    ]);

    app(CorrectAttendance::class)->handle($attendance, [
        'checked_in_at' => '2026-08-03 08:55:00',
        'checked_out_at' => '2026-08-03 17:00:00',
        'status' => AttendanceStatus::Present->value,
        'correction_reason' => 'Badge reader outage',
    ], $hr);

    $entry = AuditLog::where('event', 'attendance.corrected')->firstOrFail();

    expect($entry->old_values['status'])->toBe(AttendanceStatus::Late->value)
        ->and($entry->new_values['status'])->toBe(AttendanceStatus::Present->value)
        ->and($entry->old_values['checked_in_at'])->toBe('2026-08-03 09:30:00')
        ->and($entry->new_values['reason'])->toBe('Badge reader outage')
        ->and($entry->user_id)->toBe($hr->id);
});

it('dispatches AttendanceCorrected with the actor', function () {
    Event::fake([AttendanceCorrected::class]);
    $employee = Employee::factory()->create();
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $attendance = Attendance::create([
        'employee_id' => $employee->id,
        'date' => '2026-08-03',
        'status' => AttendanceStatus::Absent,
    ]);

    app(CorrectAttendance::class)->handle($attendance, [
        'status' => AttendanceStatus::Present->value,
        'correction_reason' => 'Worked from the Jakarta office',
    ], $hr);

    Event::assertDispatched(AttendanceCorrected::class, fn (AttendanceCorrected $e) => $e->actor->is($hr));
});

it('never records a salary figure in an audit entry', function () {
    Event::fake([LeaveRequestSubmitted::class, LeaveRequestReviewed::class]);

    $listener = app(RecordDomainAudit::class);
    $employee = Employee::factory()->create(['basic_salary' => 42_000_000]);
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $attendance = Attendance::create([
        'employee_id' => $employee->id,
        'date' => '2026-08-04',
        'status' => AttendanceStatus::Present,
    ]);

    $listener->attendanceCorrected(new AttendanceCorrected(
        $attendance,
        $hr,
        ['basic_salary' => 42_000_000, 'bank_account' => '1234567890'],
        ['status' => 'present'],
        'test',
    ));

    $entry = AuditLog::where('event', 'attendance.corrected')->firstOrFail();

    expect($entry->old_values['basic_salary'])->toBe('[REDACTED]')
        ->and($entry->old_values['bank_account'])->toBe('[REDACTED]');
});
