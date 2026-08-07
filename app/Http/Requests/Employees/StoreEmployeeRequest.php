<?php

namespace App\Http\Requests\Employees;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Employee::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'employee_number' => ['nullable', 'string', 'max:30', 'unique:employees,employee_number'],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('is_active', true)],
            'position_id' => ['required', Rule::exists('positions', 'id')->where('is_active', true)],
            'location_id' => ['nullable', Rule::exists('locations', 'id')->where('is_active', true)],
            'employment_type_id' => ['nullable', Rule::exists('employment_types', 'id')->where('is_active', true)],
            'manager_id' => ['nullable', 'exists:employees,id'],
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
}
