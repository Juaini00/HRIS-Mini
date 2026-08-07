<?php

use App\Enums\LeaveRequestStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

function approvedLeave(Employee $employee, string $start, string $end, ?LeaveType $type = null): LeaveRequest
{
    return LeaveRequest::create([
        'employee_id' => $employee->id,
        'leave_type_id' => ($type ?? LeaveType::factory()->create())->id,
        'start_date' => $start,
        'end_date' => $end,
        'days' => 1,
        'reason' => 'Strictly private reason',
        'status' => LeaveRequestStatus::Approved,
    ]);
}

it('shows an employee only their own leave, whatever scope they ask for', function () {
    $employeeUser = User::factory()->create(['role' => UserRole::Employee]);
    $mine = Employee::factory()->create(['user_id' => $employeeUser->id]);
    $someoneElse = Employee::factory()->create();

    approvedLeave($mine, today()->toDateString(), today()->toDateString());
    approvedLeave($someoneElse, today()->toDateString(), today()->toDateString());

    // Asking for the company calendar must not widen what they receive.
    $this->actingAs($employeeUser)
        ->get(route('leave.index', ['scope' => 'company']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('calendarScope', 'personal')
            ->where('calendarScopes', ['personal'])
            ->has('calendar', 1)
            ->where('calendar.0.employee_id', $mine->id));
});

it('limits a manager to their own team', function () {
    $managerUser = User::factory()->create(['role' => UserRole::Manager]);
    $manager = Employee::factory()->create(['user_id' => $managerUser->id]);
    $report = Employee::factory()->create(['manager_id' => $manager->id]);
    $outsider = Employee::factory()->create();

    approvedLeave($manager, today()->toDateString(), today()->toDateString());
    approvedLeave($report, today()->toDateString(), today()->toDateString());
    approvedLeave($outsider, today()->toDateString(), today()->toDateString());

    $this->actingAs($managerUser)
        ->get(route('leave.index', ['scope' => 'company']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            // 'company' is not offered to a manager, so it falls back to 'team'.
            ->where('calendarScope', 'team')
            ->where('calendarScopes', ['personal', 'team'])
            ->has('calendar', 2));
});

it('gives HR the whole company', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    Employee::factory()->create(['user_id' => $hr->id]);

    approvedLeave(Employee::factory()->create(), today()->toDateString(), today()->toDateString());
    approvedLeave(Employee::factory()->create(), today()->toDateString(), today()->toDateString());

    $this->actingAs($hr)
        ->get(route('leave.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('calendarScope', 'company')
            ->where('calendarScopes', ['personal', 'team', 'company'])
            ->has('calendar', 2));
});

it('never sends another employee leave reason to the browser', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    Employee::factory()->create(['user_id' => $hr->id]);
    approvedLeave(Employee::factory()->create(), today()->toDateString(), today()->toDateString());

    $response = $this->actingAs($hr)->get(route('leave.index'));

    // The calendar payload deliberately omits the reason column.
    $response->assertInertia(fn ($page) => $page->missing('calendar.0.reason'));
});

it('includes leave that merely overlaps the requested month', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    Employee::factory()->create(['user_id' => $hr->id]);

    // Spans the month boundary: starts in the previous month, ends in this one.
    approvedLeave(
        Employee::factory()->create(),
        today()->startOfMonth()->subDays(3)->toDateString(),
        today()->startOfMonth()->addDays(2)->toDateString(),
    );

    $this->actingAs($hr)
        ->get(route('leave.index', ['month' => today()->startOfMonth()->toDateString()]))
        ->assertInertia(fn ($page) => $page->has('calendar', 1));
});

it('returns holidays for the displayed month only', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    Employee::factory()->create(['user_id' => $hr->id]);

    Holiday::create(['date' => today()->startOfMonth()->addDays(5)->toDateString(), 'name' => 'In month']);
    Holiday::create(['date' => today()->startOfMonth()->subMonth()->toDateString(), 'name' => 'Previous month']);

    $this->actingAs($hr)
        ->get(route('leave.index'))
        ->assertInertia(fn ($page) => $page->has('holidays', 1)->where('holidays.0.name', 'In month'));
});
