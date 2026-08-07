<?php

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;

function employeeLifecycleFixture(UserRole $role = UserRole::Employee): Employee
{
    $department = Department::create(['name' => fake()->unique()->company(), 'code' => fake()->unique()->lexify('???')]);
    $position = Position::create(['department_id' => $department->id, 'name' => fake()->jobTitle()]);
    $user = User::factory()->create(['role' => $role]);

    return Employee::create(['user_id' => $user->id, 'employee_number' => fake()->unique()->numerify('LIF-####'), 'department_id' => $department->id, 'position_id' => $position->id, 'joined_at' => '2026-01-01', 'basic_salary' => 5_000_000]);
}

test('HR creates employee and user transactionally with salary history', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $department = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
    $position = Position::create(['department_id' => $department->id, 'name' => 'Engineer']);

    $this->actingAs($hr)->post(route('employees.store'), [
        'name' => 'New Employee', 'email' => 'new@example.test', 'department_id' => $department->id,
        'position_id' => $position->id, 'joined_at' => '2026-01-01', 'basic_salary' => 7_000_000, 'role' => UserRole::Employee->value,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $employee = Employee::whereHas('user', fn ($query) => $query->where('email', 'new@example.test'))->firstOrFail();
    expect($employee->employee_number)->toBe('EMP-2026-0001')
        ->and($employee->salaryHistories)->toHaveCount(1);
});

test('self manager and circular manager hierarchy are rejected', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $first = employeeLifecycleFixture();
    $second = employeeLifecycleFixture();
    $first->update(['manager_id' => $second->id]);

    $payload = ['name' => $second->user->name, 'email' => $second->user->email, 'department_id' => $second->department_id, 'position_id' => $second->position_id, 'manager_id' => $first->id, 'joined_at' => '2026-01-01', 'basic_salary' => 5_000_000, 'role' => UserRole::Employee->value];
    $this->actingAs($hr)->put(route('employees.update', $second), $payload)->assertSessionHasErrors('manager_id');

    $payload['manager_id'] = $second->id;
    $this->actingAs($hr)->put(route('employees.update', $second), $payload)->assertSessionHasErrors('manager_id');
});

test('deactivation disables login and clears direct reports', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $manager = employeeLifecycleFixture(UserRole::Manager);
    $report = employeeLifecycleFixture();
    $report->update(['manager_id' => $manager->id]);

    $this->actingAs($hr)->patch(route('employees.deactivate', $manager), ['ended_at' => '2026-08-07', 'reason' => 'Resigned'])->assertRedirect();

    expect($manager->user->refresh()->is_active)->toBeFalse()
        ->and($report->refresh()->manager_id)->toBeNull();
});

test('HR admin cannot modify a super admin employee', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $superAdmin = employeeLifecycleFixture(UserRole::SuperAdmin);

    $this->actingAs($hr)->put(route('employees.update', $superAdmin), [])->assertForbidden();
});

test('manager can view a direct report without salary or bank data', function () {
    $manager = employeeLifecycleFixture(UserRole::Manager);
    $report = employeeLifecycleFixture();
    $report->update(['manager_id' => $manager->id, 'bank_account' => '123456789']);

    $this->actingAs($manager->user)->get(route('employees.show', $report))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->missing('employee.basic_salary')->missing('employee.bank_account')->missing('employee.salary_histories'));
});
