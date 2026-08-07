<?php

namespace App\Actions\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Setting;
use Carbon\CarbonInterface;
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
            $startsAt = Setting::get('work_starts_at', '08:00');
            $tolerance = (int) Setting::get('late_tolerance_minutes', '15');
            $expected = Carbon::parse($now->toDateString().' '.$startsAt)->addMinutes($tolerance);
            $lateMinutes = $now->greaterThan($expected) ? $this->wholeMinutes($expected, $now) : 0;

            return Attendance::create([
                'employee_id' => $employee->id,
                'date' => $now->toDateString(),
                'checked_in_at' => $now,
                'late_minutes' => $lateMinutes,
                'status' => $lateMinutes > 0 ? AttendanceStatus::Late : AttendanceStatus::Present,
            ]);
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
            $attendance->update([
                'checked_out_at' => $now,
                'worked_minutes' => $this->wholeMinutes($attendance->checked_in_at, $now),
            ]);

            return $attendance->refresh();
        });
    }

    /**
     * Whole minutes between two instants.
     *
     * Carbon 3 returns a float from diffInMinutes, and the duration columns are integers.
     * PostgreSQL rejects the fractional value outright while SQLite silently truncates it,
     * so without this the bug only appears in production.
     */
    private function wholeMinutes(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) floor($from->diffInMinutes($to));
    }
}
