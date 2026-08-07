<?php

use App\Enums\UserRole;
use App\Http\Controllers\Hris\ReportController;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * Filter values that satisfy each report's validation rules.
 *
 * @return array<string, mixed>
 */
function reportFilters(array $filters): array
{
    $values = [];

    foreach ($filters as $filter) {
        $values[$filter] = match ($filter) {
            'from' => '2026-08-01',
            'to' => '2026-08-31',
            'date' => '2026-08-07',
            'year' => 2026,
            'days' => 90,
            'period_id' => PayrollPeriod::firstOrCreate(
                ['starts_on' => '2026-08-01', 'ends_on' => '2026-08-31'],
                ['name' => 'August 2026'],
            )->id,
            default => null,
        };
    }

    return $values;
}

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('lets HR export every report in the catalogue', function (string $key, array $meta) {
    $admin = User::factory()->create(['role' => UserRole::HrAdmin]);

    $this->actingAs($admin)
        ->get(route('reports.export', ['report' => $key, ...reportFilters($meta['filters'])]))
        ->assertSuccessful()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
})->with(fn () => collect(ReportController::catalogue())
    ->map(fn (array $meta, string $key): array => [$key, $meta])
    ->all());

it('refuses every report to a plain employee', function (string $key, array $meta) {
    $employee = User::factory()->create(['role' => UserRole::Employee]);

    $this->actingAs($employee)
        ->get(route('reports.export', ['report' => $key, ...reportFilters($meta['filters'])]))
        ->assertForbidden();
})->with(fn () => collect(ReportController::catalogue())
    ->map(fn (array $meta, string $key): array => [$key, $meta])
    ->all());

it('gives managers only the team-scoped reports', function () {
    $managerUser = User::factory()->create(['role' => UserRole::Manager]);
    Employee::factory()->create(['user_id' => $managerUser->id]);

    // Team-safe report: allowed.
    $this->actingAs($managerUser)
        ->get(route('reports.export', ['report' => 'employee-directory']))
        ->assertSuccessful();

    // Company-wide report: refused.
    $this->actingAs($managerUser)
        ->get(route('reports.export', ['report' => 'headcount-by-department']))
        ->assertForbidden();

    // Payroll needs its own permission, which managers never have.
    $period = PayrollPeriod::create(['name' => 'August 2026', 'starts_on' => '2026-08-01', 'ends_on' => '2026-08-31']);
    $this->actingAs($managerUser)
        ->get(route('reports.export', ['report' => 'payroll-summary', 'period_id' => $period->id]))
        ->assertForbidden();
});

it('scopes a manager export to their own team', function () {
    $managerUser = User::factory()->create(['role' => UserRole::Manager]);
    $manager = Employee::factory()->create(['user_id' => $managerUser->id, 'employee_number' => 'EMP-2026-9001']);
    $report = Employee::factory()->create(['manager_id' => $manager->id, 'employee_number' => 'EMP-2026-9002']);
    $outsider = Employee::factory()->create(['employee_number' => 'EMP-2026-9003']);

    $csv = $this->actingAs($managerUser)
        ->get(route('reports.export', ['report' => 'employee-directory']))
        ->streamedContent();

    expect($csv)->toContain($report->employee_number)
        ->and($csv)->not->toContain($outsider->employee_number)
        // The manager is not their own descendant, so they are absent too.
        ->and($csv)->not->toContain($manager->employee_number);
});

it('rejects an unknown report key', function () {
    $admin = User::factory()->create(['role' => UserRole::HrAdmin]);

    $this->actingAs($admin)->get('/reports/not-a-report/export')->assertNotFound();
});

it('sanitises spreadsheet formula injection in exports', function () {
    $admin = User::factory()->create(['role' => UserRole::HrAdmin]);
    $attacker = User::factory()->create(['name' => '=cmd|calc!A1']);
    Employee::factory()->create(['user_id' => $attacker->id, 'employee_number' => 'EMP-2026-9100']);

    $csv = $this->actingAs($admin)
        ->get(route('reports.export', ['report' => 'employee-directory']))
        ->streamedContent();

    expect($csv)->toContain("'=cmd|calc!A1")
        ->and($csv)->not->toContain(',=cmd|calc!A1');
});
