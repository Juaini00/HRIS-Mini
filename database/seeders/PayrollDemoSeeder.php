<?php

namespace Database\Seeders;

use App\Actions\Payroll\CalculateEmployeePayroll;
use App\Enums\PayrollPeriodStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

/**
 * Three payroll periods covering the closed / published / draft lifecycle.
 *
 * Figures come from the real {@see CalculateEmployeePayroll} action rather than random
 * numbers, so the demo payslips actually add up and recalculation stays deterministic.
 */
class PayrollDemoSeeder extends Seeder
{
    public function run(): void
    {
        $publisher = User::where('role', UserRole::HrAdmin)->first() ?? User::where('role', UserRole::SuperAdmin)->first();

        if ($publisher === null) {
            return;
        }

        $calculator = app(CalculateEmployeePayroll::class);

        $periods = [
            ['offset' => 2, 'status' => PayrollPeriodStatus::Closed],
            ['offset' => 1, 'status' => PayrollPeriodStatus::Published],
            ['offset' => 0, 'status' => PayrollPeriodStatus::Draft],
        ];

        foreach ($periods as $definition) {
            $month = today()->startOfMonth()->subMonths($definition['offset']);
            $this->period($month, $definition['status'], $publisher, $calculator);
        }
    }

    private function period(CarbonInterface $month, PayrollPeriodStatus $status, User $publisher, CalculateEmployeePayroll $calculator): void
    {
        $startsOn = $month->copy()->startOfMonth();
        $endsOn = $month->copy()->endOfMonth();
        $isFinalised = $status->payslipsAreVisible();

        $period = PayrollPeriod::updateOrCreate(
            ['starts_on' => $startsOn->toDateString(), 'ends_on' => $endsOn->toDateString()],
            [
                'name' => $month->format('F Y'),
                'year' => $month->year,
                'month' => $month->month,
                'payment_date' => $endsOn->copy()->addDays(5)->toDateString(),
                'status' => PayrollPeriodStatus::Draft,
                'generated_at' => now(),
                'generated_by' => $publisher->id,
                'published_at' => $isFinalised ? $endsOn->copy()->addDay() : null,
                'published_by' => $isFinalised ? $publisher->id : null,
                'notes' => 'Demo payroll period. Calculations are simplified and configurable.',
            ],
        );

        // Employees who were on the payroll at any point during the period.
        Employee::query()
            ->currentlyEmployed()
            ->where('joined_at', '<=', $endsOn->toDateString())
            ->each(fn (Employee $employee) => $calculator->handle($period, $employee));

        // Status is set last: the calculator refuses to touch a finalised period.
        $period->update(['status' => $status]);
    }
}
