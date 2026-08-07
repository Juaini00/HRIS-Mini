<?php

namespace App\Actions\Attendance;

use App\Enums\LeaveRequestStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordAttendance
{
    public function checkIn(Employee $employee): Attendance
    {
        return DB::transaction(function () use ($employee): Attendance {
            $today = today()->toDateString();
            if (today()->isWeekend() || Holiday::query()->where('date', $today)->exists() || LeaveRequest::query()->where('employee_id', $employee->id)->where('status', LeaveRequestStatus::Approved)->where('start_date', '<=', $today)->where('end_date', '>=', $today)->exists()) {
                throw ValidationException::withMessages(['attendance' => 'Check-in tidak tersedia pada hari non-kerja atau saat cuti.']);
            }
            if (Attendance::query()->where('employee_id', $employee->id)->where('date', $today)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['attendance' => 'Anda sudah check-in hari ini.']);
            }
            $now = now();
            $startsAt = Setting::query()->where('key', 'work_starts_at')->value('value') ?? '08:00';
            $tolerance = (int) (Setting::query()->where('key', 'late_tolerance_minutes')->value('value') ?? 15);
            $expected = Carbon::parse($now->toDateString().' '.$startsAt)->addMinutes($tolerance);

            return Attendance::create(['employee_id' => $employee->id, 'date' => $now->toDateString(), 'checked_in_at' => $now, 'late_minutes' => $now->greaterThan($expected) ? $expected->diffInMinutes($now) : 0, 'status' => 'present']);
        });
    }

    public function checkOut(Employee $employee): Attendance
    {
        return DB::transaction(function () use ($employee): Attendance {
            $attendance = Attendance::query()->where('employee_id', $employee->id)->where('date', today()->toDateString())->lockForUpdate()->first();
            if (! $attendance || $attendance->checked_out_at) {
                throw ValidationException::withMessages(['attendance' => 'Data check-in tidak ditemukan atau sudah selesai.']);
            }
            $now = now();
            if ($now->lessThanOrEqualTo($attendance->checked_in_at)) {
                throw ValidationException::withMessages(['attendance' => 'Waktu check-out harus setelah check-in.']);
            }
            $attendance->update(['checked_out_at' => $now, 'worked_minutes' => $attendance->checked_in_at->diffInMinutes($now)]);

            return $attendance->refresh();
        });
    }
}
