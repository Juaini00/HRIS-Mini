<?php

use App\Enums\UserRole;
use App\Models\User;

it('renders every main HRIS page for super admin', function (string $route) {
    $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
    $this->actingAs($admin)->get(route($route))->assertSuccessful();
})->with(['dashboard', 'employees.index', 'leave.index', 'attendance.index', 'payroll.index', 'announcements.index', 'organization.index', 'reports.index', 'company-settings.edit', 'audit-logs.index', 'notifications.index']);

it('redirects guests from every protected module', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with(['dashboard', 'employees.index', 'leave.index', 'attendance.index', 'payroll.index', 'announcements.index', 'organization.index', 'reports.index', 'company-settings.edit', 'audit-logs.index', 'notifications.index']);
