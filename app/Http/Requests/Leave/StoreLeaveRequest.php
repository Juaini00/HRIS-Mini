<?php

namespace App\Http\Requests\Leave;

use App\Models\LeaveType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->employee !== null; }

    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('is_active', true)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'duration_type' => ['required', 'in:full_day,first_half,second_half'],
            'reason' => ['required', 'string', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:5120'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $type = LeaveType::find($this->integer('leave_type_id'));
            if ($type?->requires_attachment && ! $this->hasFile('attachment')) {
                $validator->errors()->add('attachment', 'Lampiran wajib untuk jenis cuti ini.');
            }
            if ($this->input('duration_type') !== 'full_day' && $this->input('start_date') !== $this->input('end_date')) {
                $validator->errors()->add('duration_type', 'Cuti setengah hari hanya berlaku untuk satu tanggal.');
            }
        }];
    }
}
