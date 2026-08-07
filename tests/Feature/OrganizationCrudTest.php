<?php

use App\Enums\UserRole;
use App\Http\Controllers\Hris\OrganizationController;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Location;
use App\Models\Position;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->hr = User::factory()->create(['role' => UserRole::HrAdmin]);
});

it('renders a listing page for every entity', function (string $entity) {
    $this->actingAs($this->hr)
        ->get(route('organization.show', $entity))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('hris/organization')
            ->where('entity', $entity)
            ->has('records.data')
            ->has('entities', 6));
})->with(array_keys(OrganizationController::entities()));

it('rejects an unknown entity', function () {
    $this->actingAs($this->hr)->get('/organization/not-a-thing')->assertNotFound();
});

it('creates and then updates a department', function () {
    $this->actingAs($this->hr)
        ->post(route('organization.store', 'departments'), [
            'code' => 'RND',
            'name' => 'Research',
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $department = Department::where('code', 'RND')->sole();
    expect($department->name)->toBe('Research');

    $this->actingAs($this->hr)
        ->put(route('organization.update', ['entity' => 'departments', 'id' => $department->id]), [
            'code' => 'RND',
            'name' => 'Research & Development',
            'is_active' => false,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($department->refresh()->name)->toBe('Research & Development')
        ->and($department->is_active)->toBeFalse()
        ->and(AuditLog::where('event', 'department.updated')->exists())->toBeTrue();
});

it('lets a record keep its own unique values when edited', function () {
    $location = Location::create(['name' => 'Kantor Bandung', 'code' => 'BDG', 'timezone' => 'Asia/Jakarta']);

    // Re-submitting the same name must not trip the unique rule against itself.
    $this->actingAs($this->hr)
        ->put(route('organization.update', ['entity' => 'locations', 'id' => $location->id]), [
            'name' => 'Kantor Bandung',
            'code' => 'BDG',
            'city' => 'Bandung',
            'timezone' => 'Asia/Jakarta',
        ])
        ->assertSessionHasNoErrors();

    expect($location->refresh()->city)->toBe('Bandung');
});

it('rejects a duplicate code from a different record', function () {
    Department::create(['code' => 'FIN', 'name' => 'Finance']);
    $other = Department::create(['code' => 'OPS', 'name' => 'Operations']);

    $this->actingAs($this->hr)
        ->put(route('organization.update', ['entity' => 'departments', 'id' => $other->id]), [
            'code' => 'FIN',
            'name' => 'Operations',
        ])
        ->assertSessionHasErrors('code');
});

it('ignores a sort column that is not on the allow-list', function () {
    Department::create(['code' => 'AAA', 'name' => 'Alpha']);

    // An arbitrary expression must never reach the ORDER BY clause.
    $this->actingAs($this->hr)
        ->get(route('organization.show', ['entity' => 'departments', 'sort' => 'name); drop table users;--']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->where('filters.sort', 'name'));

    expect(User::count())->toBeGreaterThan(0);
});

it('filters the listing by search term', function () {
    Department::create(['code' => 'ENG', 'name' => 'Engineering']);
    Department::create(['code' => 'MKT', 'name' => 'Marketing']);

    $this->actingAs($this->hr)
        ->get(route('organization.show', ['entity' => 'departments', 'search' => 'Engine']))
        ->assertInertia(fn ($page) => $page->has('records.data', 1)->where('records.data.0.code', 'ENG'));
});

it('validates that a maximum salary cannot fall below the minimum', function () {
    $department = Department::create(['code' => 'ENG', 'name' => 'Engineering']);

    $this->actingAs($this->hr)
        ->post(route('organization.store', 'positions'), [
            'department_id' => $department->id,
            'name' => 'Engineer',
            'level' => 2,
            'min_salary' => 20_000_000,
            'max_salary' => 10_000_000,
        ])
        ->assertSessionHasErrors('max_salary');

    expect(Position::count())->toBe(0);
});

it('refuses organization management to an employee', function () {
    $employee = User::factory()->create(['role' => UserRole::Employee]);

    $this->actingAs($employee)->get(route('organization.show', 'departments'))->assertForbidden();
    $this->actingAs($employee)
        ->post(route('organization.store', 'departments'), ['code' => 'X', 'name' => 'X'])
        ->assertForbidden();
});

it('shows a manager no organization entities at all', function () {
    $manager = User::factory()->create(['role' => UserRole::Manager]);

    $this->actingAs($manager)->get(route('organization.show', 'departments'))->assertForbidden();
});
