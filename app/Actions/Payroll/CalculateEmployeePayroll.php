<?php

namespace App\Actions\Payroll;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\SalaryComponent;
use App\Services\WorkingDayCalculator;

class CalculateEmployeePayroll
{
    public function __construct(private WorkingDayCalculator $workingDays) {}

    public function handle(PayrollPeriod $period, Employee $employee): PayrollRecord
    {
        $salary = (float) ($employee->salaryHistories()->where('effective_from', '<=', $period->ends_on)->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $period->starts_on))->latest('effective_from')->value('amount') ?? $employee->basic_salary);
        $workDays = max(1, $this->workingDays->between($period->starts_on, $period->ends_on));
        $absenceDays = Attendance::query()->where('employee_id', $employee->id)->whereBetween('date', [$period->starts_on, $period->ends_on])->where('status', 'absent')->count();
        $unpaidDays = (float) LeaveRequest::query()->where('employee_id', $employee->id)->where('status', 'approved')->whereHas('leaveType', fn ($query) => $query->where('is_paid', false))->where('start_date', '<=', $period->ends_on)->where('end_date', '>=', $period->starts_on)->sum('days');
        $record = PayrollRecord::updateOrCreate(['payroll_period_id' => $period->id, 'employee_id' => $employee->id], ['basic_salary' => round($salary, 2), 'earnings' => 0, 'deductions' => 0, 'net_salary' => round($salary, 2), 'breakdown' => ['work_days' => $workDays, 'absence_days' => $absenceDays, 'unpaid_leave_days' => $unpaidDays]]);
        $record->items()->where('is_manual', false)->delete();
        $earnings = 0.0; $deductions = 0.0;
        $components = SalaryComponent::query()->where('is_active', true)->whereHas('employees', fn ($query) => $query->whereKey($employee->id)->wherePivot('effective_from', '<=', $period->ends_on)->where(fn ($dates) => $dates->whereNull('employee_salary_components.effective_to')->orWhere('employee_salary_components.effective_to', '>=', $period->starts_on)))->with(['employees' => fn ($query) => $query->whereKey($employee->id)])->get();
        foreach ($components as $component) {
            $value = (float) ($component->employees->first()?->pivot->override_value ?? $component->value);
            $amount = round($component->calculation_type === 'percentage' ? $salary * $value / 100 : $value, 2);
            $record->items()->create(['salary_component_id' => $component->id, 'name' => $component->name, 'type' => $component->type, 'amount' => $amount]);
            $component->type === 'earning' ? $earnings += $amount : $deductions += $amount;
        }
        $attendanceDeduction = round($salary / $workDays * ($absenceDays + $unpaidDays), 2);
        if ($attendanceDeduction > 0) { $record->items()->create(['name' => 'Absence and unpaid leave', 'type' => 'deduction', 'amount' => $attendanceDeduction]); $deductions += $attendanceDeduction; }
        $manualEarnings = (float) $record->items()->where('is_manual', true)->where('type', 'earning')->sum('amount');
        $manualDeductions = (float) $record->items()->where('is_manual', true)->where('type', 'deduction')->sum('amount');
        $record->update(['earnings' => round($earnings + $manualEarnings, 2), 'deductions' => round($deductions + $manualDeductions, 2), 'net_salary' => round($salary + $earnings + $manualEarnings - $deductions - $manualDeductions, 2)]);

        return $record->load('items');
    }
}
