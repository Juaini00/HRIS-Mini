<?php

namespace App\Http\Requests\Settings;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === UserRole::SuperAdmin;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'work_starts_at' => ['required', 'date_format:H:i'],
            'work_ends_at' => ['required', 'date_format:H:i', 'after:work_starts_at'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'currency' => ['required', 'in:IDR'],
        ];
    }
}
