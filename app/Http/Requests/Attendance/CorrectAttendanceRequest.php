<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class CorrectAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdministrator();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'checked_in_at' => ['nullable', 'date'],
            'checked_out_at' => ['nullable', 'date', 'after:checked_in_at'],
            'status' => ['required', 'in:present,absent,leave,holiday,incomplete'],
            'correction_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
