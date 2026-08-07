<?php

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->employeeUser = User::factory()->create(['role' => UserRole::Employee]);
    $this->employee = Employee::factory()->create(['user_id' => $this->employeeUser->id]);
    $this->hr = User::factory()->create(['role' => UserRole::HrAdmin]);

    $this->attendance = Attendance::create([
        'employee_id' => $this->employee->id,
        'date' => '2026-08-03',
        'checked_in_at' => '2026-08-03 09:40:00',
        'late_minutes' => 40,
        'status' => AttendanceStatus::Late,
    ]);
});

it('lets an employee request a correction without changing the record', function () {
    $this->actingAs($this->employeeUser)
        ->post(route('attendance.corrections.store', $this->attendance), [
            'reason' => 'The badge reader at the north entrance was offline that morning.',
            'checked_in_at' => '2026-08-03 08:55:00',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $correction = AttendanceCorrection::sole();

    expect($correction->status)->toBe(AttendanceCorrection::STATUS_PENDING)
        ->and($correction->requested_by)->toBe($this->employeeUser->id)
        // Crucially, the attendance row itself is untouched until HR approves.
        ->and($this->attendance->refresh()->checked_in_at->format('H:i'))->toBe('09:40')
        ->and($this->attendance->status)->toBe(AttendanceStatus::Late);
});

it('refuses a correction request for somebody else attendance', function () {
    $stranger = User::factory()->create(['role' => UserRole::Employee]);
    Employee::factory()->create(['user_id' => $stranger->id]);

    $this->actingAs($stranger)
        ->post(route('attendance.corrections.store', $this->attendance), [
            'reason' => 'I would like to change a record that is not mine at all.',
        ])
        ->assertForbidden();

    expect(AttendanceCorrection::count())->toBe(0);
});

it('rejects a second pending request for the same record', function () {
    $payload = ['reason' => 'The badge reader at the north entrance was offline that morning.'];

    $this->actingAs($this->employeeUser)
        ->post(route('attendance.corrections.store', $this->attendance), $payload)
        ->assertSessionHasNoErrors();

    $this->actingAs($this->employeeUser)
        ->post(route('attendance.corrections.store', $this->attendance), $payload)
        ->assertSessionHasErrors('reason');

    expect(AttendanceCorrection::count())->toBe(1);
});

it('applies the requested values and audits them when HR approves', function () {
    $this->actingAs($this->employeeUser)->post(route('attendance.corrections.store', $this->attendance), [
        'reason' => 'The badge reader at the north entrance was offline that morning.',
        'checked_in_at' => '2026-08-03 08:55:00',
        'checked_out_at' => '2026-08-03 17:05:00',
    ]);

    $correction = AttendanceCorrection::sole();

    $this->actingAs($this->hr)
        ->patch(route('attendance.corrections.review', $correction), [
            'decision' => 'approve',
            'review_notes' => 'Confirmed with facilities.',
        ])
        ->assertRedirect();

    $attendance = $this->attendance->refresh();

    expect($correction->refresh()->status)->toBe(AttendanceCorrection::STATUS_APPROVED)
        ->and($correction->reviewed_by)->toBe($this->hr->id)
        ->and($attendance->checked_in_at->format('H:i'))->toBe('08:55')
        ->and($attendance->checked_out_at->format('H:i'))->toBe('17:05')
        // Approval flows through the same audited path as a direct HR correction.
        ->and(AuditLog::where('event', 'attendance.corrected')->exists())->toBeTrue();
});

it('leaves the record alone when HR rejects', function () {
    $this->actingAs($this->employeeUser)->post(route('attendance.corrections.store', $this->attendance), [
        'reason' => 'The badge reader at the north entrance was offline that morning.',
        'checked_in_at' => '2026-08-03 08:55:00',
    ]);

    $correction = AttendanceCorrection::sole();

    $this->actingAs($this->hr)
        ->patch(route('attendance.corrections.review', $correction), [
            'decision' => 'reject',
            'review_notes' => 'The access log shows a successful scan at 09:40.',
        ])
        ->assertRedirect();

    expect($correction->refresh()->status)->toBe(AttendanceCorrection::STATUS_REJECTED)
        ->and($this->attendance->refresh()->checked_in_at->format('H:i'))->toBe('09:40');
});

it('never lets an employee review their own request', function () {
    $this->actingAs($this->employeeUser)->post(route('attendance.corrections.store', $this->attendance), [
        'reason' => 'The badge reader at the north entrance was offline that morning.',
        'checked_in_at' => '2026-08-03 08:55:00',
    ]);

    $this->actingAs($this->employeeUser)
        ->patch(route('attendance.corrections.review', AttendanceCorrection::sole()), ['decision' => 'approve'])
        ->assertForbidden();

    expect(AttendanceCorrection::sole()->status)->toBe(AttendanceCorrection::STATUS_PENDING)
        ->and($this->attendance->refresh()->checked_in_at->format('H:i'))->toBe('09:40');
});

it('refuses to decide the same request twice', function () {
    $this->actingAs($this->employeeUser)->post(route('attendance.corrections.store', $this->attendance), [
        'reason' => 'The badge reader at the north entrance was offline that morning.',
    ]);

    $correction = AttendanceCorrection::sole();

    $this->actingAs($this->hr)->patch(route('attendance.corrections.review', $correction), ['decision' => 'approve']);

    $this->actingAs($this->hr)
        ->patch(route('attendance.corrections.review', $correction), ['decision' => 'reject'])
        ->assertSessionHasErrors('status');

    expect($correction->refresh()->status)->toBe(AttendanceCorrection::STATUS_APPROVED);
});

it('shows an employee only their own correction requests', function () {
    $other = User::factory()->create(['role' => UserRole::Employee]);
    $otherEmployee = Employee::factory()->create(['user_id' => $other->id]);
    $otherAttendance = Attendance::create([
        'employee_id' => $otherEmployee->id,
        'date' => '2026-08-03',
        'status' => AttendanceStatus::Absent,
    ]);

    $this->actingAs($this->employeeUser)->post(route('attendance.corrections.store', $this->attendance), [
        'reason' => 'The badge reader at the north entrance was offline that morning.',
    ]);
    $this->actingAs($other)->post(route('attendance.corrections.store', $otherAttendance), [
        'reason' => 'I was working from the Surabaya office and forgot to check in.',
    ]);

    $this->actingAs($this->employeeUser)
        ->get(route('attendance.index'))
        ->assertInertia(fn ($page) => $page->has('corrections', 1));

    $this->actingAs($this->hr)
        ->get(route('attendance.index'))
        ->assertInertia(fn ($page) => $page->has('corrections', 2));
});
