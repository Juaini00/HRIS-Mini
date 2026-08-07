<?php

namespace App\Actions\Attendance;

use App\Models\AttendanceCorrection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * HR decides on an employee's correction request.
 *
 * Approving applies the requested values through {@see CorrectAttendance}, so the change
 * lands in the audit trail by exactly the same route as a direct HR correction.
 */
class ReviewAttendanceCorrection
{
    public function __construct(private CorrectAttendance $correct) {}

    public function handle(
        AttendanceCorrection $correction,
        User $reviewer,
        bool $approve,
        ?string $notes = null,
    ): AttendanceCorrection {
        $correction = DB::transaction(function () use ($correction, $reviewer, $approve, $notes): AttendanceCorrection {
            $locked = AttendanceCorrection::query()->lockForUpdate()->findOrFail($correction->id);

            if ($locked->status !== AttendanceCorrection::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'status' => 'Permintaan koreksi ini sudah diproses.',
                ]);
            }

            $locked->update([
                'status' => $approve ? AttendanceCorrection::STATUS_APPROVED : AttendanceCorrection::STATUS_REJECTED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_notes' => $notes,
            ]);

            return $locked->refresh();
        });

        if ($approve) {
            $this->correct->handle(
                $correction->attendance,
                [
                    ...$correction->new_values ?? [],
                    'correction_reason' => 'Permintaan karyawan: '.$correction->reason,
                    'updated_by' => $reviewer->id,
                ],
                $reviewer,
            );
        }

        return $correction;
    }
}
