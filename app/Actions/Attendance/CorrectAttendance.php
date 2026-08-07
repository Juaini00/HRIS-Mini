<?php

namespace App\Actions\Attendance;

use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CorrectAttendance
{
    public function handle(Attendance $attendance, array $data): Attendance
    {
        return DB::transaction(function () use ($attendance, $data): Attendance {
            $attendance = Attendance::query()->lockForUpdate()->findOrFail($attendance->id);
            $checkIn = isset($data['checked_in_at']) ? Carbon::parse($data['checked_in_at']) : null;
            $checkOut = isset($data['checked_out_at']) ? Carbon::parse($data['checked_out_at']) : null;
            $attendance->update([...$data, 'worked_minutes' => $checkIn && $checkOut ? $checkIn->diffInMinutes($checkOut) : 0]);

            return $attendance->refresh();
        });
    }
}
