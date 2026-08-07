<?php

namespace App\Http\Requests\Employees;

use App\Enums\EmploymentStatus;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeactivateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('deactivate', $this->route('employee'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');
        abort_unless($employee instanceof Employee, 404);

        return [
            'ended_at' => ['required', 'date', 'after_or_equal:'.$employee->joined_at->toDateString()],
            'reason' => ['required', 'string', 'max:1000'],
            'employment_status' => ['nullable', Rule::in([EmploymentStatus::Resigned->value, EmploymentStatus::Terminated->value])],
        ];
    }
}
