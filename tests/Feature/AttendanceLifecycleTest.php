<?php

use App\Enums\LeaveStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\Date;

function attendanceEmployee(): Employee
{
    $department = Department::create(['name' => fake()->unique()->company(), 'code' => fake()->unique()->lexify('???')]);
    $position = Position::create(['department_id' => $department->id, 'name' => fake()->jobTitle()]);
    $user = User::factory()->create();
    return Employee::create(['user_id' => $user->id, 'employee_number' => fake()->unique()->numerify('ATT-####'), 'department_id' => $department->id, 'position_id' => $position->id, 'joined_at' => '2025-01-01', 'basic_salary' => 1]);
}

test('HR correction requires reason, recalculates duration, and is audited', function () {
    $employee = attendanceEmployee(); $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $attendance = Attendance::create(['employee_id' => $employee->id, 'date' => '2026-08-07', 'status' => 'incomplete']);
    $this->actingAs($hr)->patch(route('attendance.correct', $attendance), ['checked_in_at' => '2026-08-07 08:00:00', 'checked_out_at' => '2026-08-07 17:00:00', 'status' => 'present', 'correction_reason' => 'Timesheet verification'])->assertRedirect()->assertSessionHasNoErrors();
    expect($attendance->refresh()->worked_minutes)->toBe(540)->and(AuditLog::where('event', 'attendance.corrected')->exists())->toBeTrue();
});

test('daily absence processing is idempotent and respects leave holiday and weekend', function () {
    $employee = attendanceEmployee(); $type = LeaveType::create(['name' => 'Annual', 'annual_quota' => 12]);
    LeaveRequest::create(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'start_date' => '2026-08-10', 'end_date' => '2026-08-10', 'days' => 1, 'reason' => 'Rest', 'status' => LeaveStatus::Approved]);
    $this->artisan('nusahr:process-absences', ['date' => '2026-08-10'])->assertSuccessful();
    $this->artisan('nusahr:process-absences', ['date' => '2026-08-10'])->assertSuccessful();
    expect(Attendance::where('employee_id', $employee->id)->where('date', '2026-08-10')->sole()->status)->toBe('leave')->and(Attendance::count())->toBe(1);

    Holiday::create(['date' => '2026-08-11', 'name' => 'Holiday']);
    $this->artisan('nusahr:process-absences', ['date' => '2026-08-11'])->assertSuccessful();
    $this->artisan('nusahr:process-absences', ['date' => '2026-08-09'])->assertSuccessful();
    expect(Attendance::count())->toBe(1);
});

test('missing checkout becomes incomplete', function () {
    Date::setTestNow('2026-08-07 23:30:00'); $employee = attendanceEmployee();
    Attendance::create(['employee_id' => $employee->id, 'date' => '2026-08-07', 'checked_in_at' => '2026-08-07 08:00:00', 'status' => 'present']);
    $this->artisan('nusahr:process-absences', ['date' => '2026-08-07'])->assertSuccessful();
    expect(Attendance::sole()->status)->toBe('incomplete');
});
