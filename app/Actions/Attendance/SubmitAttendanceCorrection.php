<?php

namespace App\Actions\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An employee asks HR to fix one of their own attendance records.
 *
 * The request only stores what was asked for; nothing on the attendance row changes until
 * HR approves. That separation is the point — employees must never be able to edit raw
 * timestamps, but they do need a way to report a badge reader that failed.
 */
class SubmitAttendanceCorrection
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Attendance $attendance, User $requester, array $data): AttendanceCorrection
    {
        return DB::transaction(function () use ($attendance, $requester, $data): AttendanceCorrection {
            $alreadyPending = AttendanceCorrection::query()
                ->where('attendance_id', $attendance->id)
                ->where('status', AttendanceCorrection::STATUS_PENDING)
                ->lockForUpdate()
                ->exists();

            if ($alreadyPending) {
                throw ValidationException::withMessages([
                    'reason' => 'Sudah ada permintaan koreksi yang menunggu peninjauan untuk tanggal ini.',
                ]);
            }

            return AttendanceCorrection::create([
                'attendance_id' => $attendance->id,
                'requested_by' => $requester->id,
                'status' => AttendanceCorrection::STATUS_PENDING,
                'reason' => $data['reason'],
                'old_values' => [
                    'checked_in_at' => $attendance->checked_in_at?->format('Y-m-d H:i:s'),
                    'checked_out_at' => $attendance->checked_out_at?->format('Y-m-d H:i:s'),
                    'status' => $attendance->status->value,
                ],
                'new_values' => array_filter([
                    'checked_in_at' => $data['checked_in_at'] ?? null,
                    'checked_out_at' => $data['checked_out_at'] ?? null,
                ], fn ($value): bool => $value !== null),
            ]);
        });
    }
}
