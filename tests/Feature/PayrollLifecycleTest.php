<?php

use App\Actions\Payroll\GeneratePayrollPeriod;
use App\Enums\PayrollPeriodStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Notifications\PayrollPublishedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

function payrollEmployee(float $salary = 10_000_000): Employee
{
    $department = Department::create(['name' => fake()->unique()->company(), 'code' => fake()->unique()->lexify('???')]);
    $position = Position::create(['department_id' => $department->id, 'name' => fake()->jobTitle()]);
    $user = User::factory()->create();

    return Employee::create(['user_id' => $user->id, 'employee_number' => fake()->unique()->numerify('PAY-####'), 'department_id' => $department->id, 'position_id' => $position->id, 'joined_at' => '2025-01-01', 'basic_salary' => $salary]);
}

test('payroll calculates fixed percentage and absence deductions deterministically', function () {
    $employee = payrollEmployee();
    $fixed = SalaryComponent::create(['name' => 'Transport', 'type' => 'earning', 'calculation_type' => 'fixed', 'value' => 500000]);
    $percent = SalaryComponent::create(['name' => 'Allowance', 'type' => 'earning', 'calculation_type' => 'percentage', 'value' => 10]);
    $employee->salaryComponents()->attach([$fixed->id => ['effective_from' => '2026-08-01'], $percent->id => ['effective_from' => '2026-08-01']]);
    Attendance::create(['employee_id' => $employee->id, 'date' => '2026-08-03', 'status' => 'absent']);
    $action = app(GeneratePayrollPeriod::class);
    $period = $action->handle(['name' => 'August 2026', 'starts_on' => '2026-08-01', 'ends_on' => '2026-08-31']);
    $first = $period->records->first()->net_salary;
    $second = $action->handle(['name' => 'August 2026', 'starts_on' => '2026-08-01', 'ends_on' => '2026-08-31'])->records->first()->net_salary;
    expect($first)->toBe($second)->and((float) $first)->toBeGreaterThan(10_000_000)->and($period->records->first()->items)->toHaveCount(3);
});

test('employee sees only own published payroll and receives publication notification', function () {
    Notification::fake();
    $employee = payrollEmployee();
    $other = payrollEmployee();
    $admin = User::factory()->create(['role' => UserRole::HrAdmin]);
    $period = app(GeneratePayrollPeriod::class)->handle(['name' => 'August 2026', 'starts_on' => '2026-08-01', 'ends_on' => '2026-08-31']);
    $this->actingAs($employee->user)->get(route('payroll.index'))->assertSuccessful()->assertInertia(fn ($page) => $page->where('periods.data', []));
    $this->actingAs($admin)->post(route('payroll.publish', $period))->assertRedirect();
    Notification::assertSentTo($employee->user, PayrollPublishedNotification::class);
    Notification::assertSentTo($other->user, PayrollPublishedNotification::class);
    $this->actingAs($employee->user)->get(route('payroll.index'))->assertInertia(fn ($page) => $page->has('periods.data.0.records', 1));
});

test('published payroll cannot be regenerated or adjusted', function () {
    $employee = payrollEmployee();
    $admin = User::factory()->create(['role' => UserRole::HrAdmin]);
    $period = app(GeneratePayrollPeriod::class)->handle(['name' => 'August', 'starts_on' => '2026-08-01', 'ends_on' => '2026-08-31']);
    $period->update(['status' => PayrollPeriodStatus::Published]);
    expect(fn () => app(GeneratePayrollPeriod::class)->handle(['name' => 'August', 'starts_on' => '2026-08-01', 'ends_on' => '2026-08-31']))->toThrow(ValidationException::class);
    $this->actingAs($admin)->post(route('payroll.adjustments.store', $period->records->first()), ['name' => 'Bonus', 'type' => 'earning', 'amount' => 100, 'notes' => 'Manual bonus'])->assertForbidden();
});

test('employee cannot view draft or another employees payslip', function () {
    $employee = payrollEmployee();
    $other = payrollEmployee();
    $period = app(GeneratePayrollPeriod::class)->handle(['name' => 'August', 'starts_on' => '2026-08-01', 'ends_on' => '2026-08-31']);
    $own = $period->records->firstWhere('employee_id', $employee->id);
    $otherRecord = $period->records->firstWhere('employee_id', $other->id);
    $this->actingAs($employee->user)->get(route('payslips.show', $own))->assertForbidden();
    $period->update(['status' => PayrollPeriodStatus::Published]);
    $this->actingAs($employee->user)->get(route('payslips.show', $own))->assertSuccessful();
    $this->actingAs($employee->user)->get(route('payslips.show', $otherRecord))->assertForbidden();
});
