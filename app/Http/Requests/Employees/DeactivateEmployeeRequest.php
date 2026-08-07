<?php

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;

class DeactivateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('deactivate', $this->route('employee'));
    }

    public function rules(): array
    {
        return ['ended_at' => ['required', 'date', 'after_or_equal:'.$this->route('employee')->joined_at->toDateString()], 'reason' => ['required', 'string', 'max:1000']];
    }
}
