<?php

namespace App\Http\Requests\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attendance = $this->route('attendance');

        return $attendance instanceof Attendance
            && $this->user()->can('create', [AttendanceCorrection::class, $attendance]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'checked_in_at' => ['nullable', 'date'],
            'checked_out_at' => ['nullable', 'date', 'after:checked_in_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.min' => 'Jelaskan alasan koreksi minimal 10 karakter agar HR dapat menilainya.',
            'checked_out_at.after' => 'Waktu pulang harus setelah waktu masuk.',
        ];
    }
}
