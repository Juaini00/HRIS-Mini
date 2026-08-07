<?php

use App\Actions\Attendance\CorrectAttendance;
use App\Actions\Attendance\RecordAttendance;
use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Date;

/**
 * Duration columns are integers. Carbon 3 returns a float from diffInMinutes, and only
 * PostgreSQL rejects that — SQLite truncates silently — so these assert the PHP value is
 * a whole integer rather than relying on the database to complain.
 */
beforeEach(function () {
    Setting::updateOrCreate(['key' => 'work_starts_at'], ['value' => '09:00']);
    Setting::updateOrCreate(['key' => 'late_tolerance_minutes'], ['value' => '15']);
    $this->employee = Employee::factory()->create();
});

afterEach(fn () => Date::setTestNow());

it('records whole minutes when checking out after a fractional interval', function () {
    // 08:55:12 to 16:03:00 is 427.8 minutes — the exact shape that broke in production.
    Date::setTestNow('2026-08-07 08:55:12');
    app(RecordAttendance::class)->checkIn($this->employee);

    Date::setTestNow('2026-08-07 16:03:00');
    $attendance = app(RecordAttendance::class)->checkOut($this->employee);

    expect($attendance->worked_minutes)->toBeInt()
        ->and($attendance->worked_minutes)->toBe(427);
});

it('records whole late minutes on a fractional check-in', function () {
    // Grace period ends 09:15; arriving at 09:40:37 is 25.6 minutes late.
    Date::setTestNow('2026-08-07 09:40:37');

    $attendance = app(RecordAttendance::class)->checkIn($this->employee);

    expect($attendance->late_minutes)->toBeInt()
        ->and($attendance->late_minutes)->toBe(25)
        ->and($attendance->status)->toBe(AttendanceStatus::Late);
});

it('marks an on-time arrival as present with no late minutes', function () {
    Date::setTestNow('2026-08-07 09:05:00');

    $attendance = app(RecordAttendance::class)->checkIn($this->employee);

    expect($attendance->late_minutes)->toBe(0)
        ->and($attendance->status)->toBe(AttendanceStatus::Present);
});

it('records whole minutes when HR corrects a record', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $attendance = Attendance::create([
        'employee_id' => $this->employee->id,
        'date' => '2026-08-07',
        'status' => AttendanceStatus::Absent,
    ]);

    $corrected = app(CorrectAttendance::class)->handle($attendance, [
        'checked_in_at' => '2026-08-07 08:55:12',
        'checked_out_at' => '2026-08-07 16:03:00',
        'status' => AttendanceStatus::Present->value,
        'correction_reason' => 'Badge reader outage',
    ], $hr);

    expect($corrected->worked_minutes)->toBeInt()
        ->and($corrected->worked_minutes)->toBe(427);
});
