<?php

namespace App\Actions\Payroll;

use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GeneratePayrollPeriod
{
    public function __construct(private CalculateEmployeePayroll $calculator) {}

    public function handle(array $data): PayrollPeriod
    {
        return DB::transaction(function () use ($data): PayrollPeriod {
            $period = PayrollPeriod::firstOrCreate(['starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on']], ['name' => $data['name']]);
            if ($period->status === PayrollStatus::Published) { throw ValidationException::withMessages(['period' => 'Payroll yang sudah dipublikasikan tidak dapat dibuat ulang.']); }
            Employee::query()->whereDate('joined_at', '<=', $period->ends_on)->where(fn ($query) => $query->whereNull('ended_at')->orWhereDate('ended_at', '>=', $period->starts_on))->each(fn (Employee $employee) => $this->calculator->handle($period, $employee));

            return $period->load('records.items');
        });
    }
}
