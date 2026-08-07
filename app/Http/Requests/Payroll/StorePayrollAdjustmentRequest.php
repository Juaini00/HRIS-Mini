<?php

namespace App\Http\Requests\Payroll;

use App\Enums\PayrollStatus;
use Illuminate\Foundation\Http\FormRequest;

class StorePayrollAdjustmentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->isAdministrator() && $this->route('payrollRecord')->period->status === PayrollStatus::Draft; }
    public function rules(): array { return ['name' => ['required', 'string', 'max:150'], 'type' => ['required', 'in:earning,deduction'], 'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999.99'], 'notes' => ['required', 'string', 'max:1000']]; }
}
