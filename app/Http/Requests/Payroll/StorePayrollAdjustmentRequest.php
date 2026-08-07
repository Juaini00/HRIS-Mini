<?php

namespace App\Http\Requests\Payroll;

use App\Enums\PayrollPeriodStatus;
use App\Models\PayrollRecord;
use Illuminate\Foundation\Http\FormRequest;

class StorePayrollAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('payrollRecord');

        return $this->user()->isAdministrator()
            && $record instanceof PayrollRecord
            && $record->period->status === PayrollPeriodStatus::Draft;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150'], 'type' => ['required', 'in:earning,deduction'], 'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999.99'], 'notes' => ['required', 'string', 'max:1000']];
    }
}
