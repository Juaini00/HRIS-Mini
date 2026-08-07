<?php

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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

test('operator-supplied password lets the new employee log in immediately', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $department = Department::create(['name' => 'Support', 'code' => 'SUP']);
    $position = Position::create(['department_id' => $department->id, 'name' => 'Agent']);

    $this->actingAs($hr)->post(route('employees.store'), [
        'name' => 'Login Ready', 'email' => 'ready@example.test', 'department_id' => $department->id,
        'position_id' => $position->id, 'joined_at' => '2026-01-01', 'basic_salary' => 6_000_000, 'role' => UserRole::Employee->value,
        'password' => 'Secret123!', 'password_confirmation' => 'Secret123!',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $user = User::where('email', 'ready@example.test')->firstOrFail();
    expect(Hash::check('Secret123!', $user->password))->toBeTrue();
});

test('HR can update an employee personal profile', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $employee = employeeLifecycleFixture();

    $this->actingAs($hr)->put(route('employees.profile.update', $employee), [
        'first_name' => 'Budi', 'last_name' => 'Santoso', 'gender' => 'male',
        'marital_status' => 'married', 'city' => 'Bandung',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $employee->refresh();
    expect($employee->first_name)->toBe('Budi')
        ->and($employee->gender->value)->toBe('male')
        ->and($employee->city)->toBe('Bandung');
});

test('HR can update bank and tax details from the profile form', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $employee = employeeLifecycleFixture();

    $this->actingAs($hr)->put(route('employees.profile.update', $employee), [
        'bank_name' => 'Bank Mandiri', 'bank_account' => '1234567890',
        'bank_account_holder' => $employee->user->name, 'tax_number' => '09.876.543.2-123.000',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $employee->refresh();
    expect($employee->bank_name)->toBe('Bank Mandiri')
        ->and($employee->bank_account)->toBe('1234567890')
        ->and($employee->tax_number)->toBe('09.876.543.2-123.000');
});

test('changing salary twice in the same day updates the existing history row instead of duplicating it', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $employee = employeeLifecycleFixture();
    $payload = ['name' => $employee->user->name, 'email' => $employee->user->email, 'department_id' => $employee->department_id, 'position_id' => $employee->position_id, 'joined_at' => '2026-01-01', 'role' => UserRole::Employee->value];

    $this->actingAs($hr)->put(route('employees.update', $employee), [...$payload, 'basic_salary' => 6_000_000])
        ->assertRedirect()->assertSessionHasNoErrors();
    $this->actingAs($hr)->put(route('employees.update', $employee), [...$payload, 'basic_salary' => 7_000_000])
        ->assertRedirect()->assertSessionHasNoErrors();

    $employee->refresh();
    expect((float) $employee->basic_salary)->toBe(7_000_000.0)
        ->and($employee->salaryHistories()->whereDate('effective_from', today())->count())->toBe(1)
        ->and((float) $employee->salaryHistories()->whereDate('effective_from', today())->value('amount'))->toBe(7_000_000.0);
});

test('HR can change the employment status through the update form', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $employee = employeeLifecycleFixture();

    $this->actingAs($hr)->put(route('employees.update', $employee), [
        'name' => $employee->user->name, 'email' => $employee->user->email,
        'department_id' => $employee->department_id, 'position_id' => $employee->position_id,
        'joined_at' => '2026-01-01', 'basic_salary' => 5_000_000, 'role' => UserRole::Employee->value,
        'employment_status' => 'suspended',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($employee->refresh()->employment_status->value)->toBe('suspended');
});

test('deactivation records the chosen exit status', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $employee = employeeLifecycleFixture();

    $this->actingAs($hr)->patch(route('employees.deactivate', $employee), [
        'ended_at' => '2026-08-07', 'reason' => 'Kontrak berakhir', 'employment_status' => 'terminated',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $employee->refresh();
    expect($employee->employment_status->value)->toBe('terminated')
        ->and($employee->ended_at->toDateString())->toBe('2026-08-07');
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

test('employee index search and status filter narrow the list', function () {
    $hr = User::factory()->create(['role' => UserRole::HrAdmin]);
    $active = employeeLifecycleFixture();
    $active->user->update(['name' => 'Findable Person']);
    $resigned = employeeLifecycleFixture();
    $resigned->update(['employment_status' => 'resigned', 'ended_at' => '2020-01-01']);

    $this->actingAs($hr)->get(route('employees.index', ['status' => 'active']))
        ->assertInertia(fn ($page) => $page
            ->where('employees.data', fn ($data) => collect($data)->pluck('id')->contains($active->id) && ! collect($data)->pluck('id')->contains($resigned->id)));

    $this->actingAs($hr)->get(route('employees.index', ['status' => 'resigned']))
        ->assertInertia(fn ($page) => $page
            ->where('employees.data', fn ($data) => collect($data)->pluck('id')->contains($resigned->id) && ! collect($data)->pluck('id')->contains($active->id)));

    $this->actingAs($hr)->get(route('employees.index', ['status' => 'all', 'search' => 'Findable Person']))
        ->assertInertia(fn ($page) => $page
            ->where('employees.data', fn ($data) => collect($data)->pluck('id')->contains($active->id) && ! collect($data)->pluck('id')->contains($resigned->id)));
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
