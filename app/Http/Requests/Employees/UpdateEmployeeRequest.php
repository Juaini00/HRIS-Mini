<?php

namespace App\Http\Requests\Employees;

use App\Enums\UserRole;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->employee());
    }

    /**
     * The bound employee, typed. Route model binding guarantees the instance, but the
     * router's signature is `object|string`, so narrow it once instead of everywhere.
     */
    private function employee(): Employee
    {
        $employee = $this->route('employee');
        abort_unless($employee instanceof Employee, 404);

        return $employee;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $employee = $this->employee();

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employee->user_id)],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('is_active', true)],
            'position_id' => ['required', Rule::exists('positions', 'id')->where('is_active', true)],
            'location_id' => ['nullable', Rule::exists('locations', 'id')->where('is_active', true)],
            'employment_type_id' => ['nullable', Rule::exists('employment_types', 'id')->where('is_active', true)],
            'manager_id' => ['nullable', 'exists:employees,id', Rule::notIn([$employee->id])],
            'joined_at' => ['required', 'date'],
            'basic_salary' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'role' => ['required', Rule::in($this->user()->role === UserRole::SuperAdmin ? array_column(UserRole::cases(), 'value') : [UserRole::HrAdmin->value, UserRole::Manager->value, UserRole::Employee->value])],
            'phone' => ['nullable', 'string', 'max:30'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'emergency_contact' => ['nullable', 'array'],
            'emergency_contact.name' => ['required_with:emergency_contact', 'string', 'max:100'],
            'emergency_contact.phone' => ['required_with:emergency_contact', 'string', 'max:30'],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $employee = $this->employee();
            $managerId = $this->integer('manager_id') ?: null;
            $visited = [];
            while ($managerId !== null) {
                if ($managerId === $employee->id || in_array($managerId, $visited, true)) {
                    $validator->errors()->add('manager_id', 'Hierarki manager tidak boleh membentuk siklus.');
                    break;
                }
                $visited[] = $managerId;
                $managerId = Employee::query()->whereKey($managerId)->value('manager_id');
            }
        }];
    }
}
