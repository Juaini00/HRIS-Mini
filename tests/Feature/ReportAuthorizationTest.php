<?php

use App\Enums\UserRole;
use App\Models\User;

it('enforces report export permissions', function (string $route, array $parameters = []) {
    $employee = User::factory()->create(['role' => UserRole::Employee]); $admin = User::factory()->create(['role' => UserRole::HrAdmin]);
    $this->actingAs($employee)->get(route($route, $parameters))->assertForbidden();
    $this->actingAs($admin)->get(route($route, $parameters))->assertSuccessful()->assertHeader('content-type', 'text/csv; charset=UTF-8');
})->with([
    'employees' => ['reports.employees', []],
    'attendance' => ['reports.attendance', ['from' => '2026-08-01', 'to' => '2026-08-31']],
    'leave' => ['reports.leave', ['from' => '2026-08-01', 'to' => '2026-08-31']],
]);
