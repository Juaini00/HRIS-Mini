<?php

namespace App\Actions\Attendance;

use App\Events\AttendanceCorrected;
use App\Models\Attendance;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CorrectAttendance
{
    /**
     * Apply an HR correction to an attendance record.
     *
     * The values before the change are captured inside the transaction and handed to the
     * event, so the audit trail records what was actually altered rather than re-reading
     * a row that no longer holds the old data.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Attendance $attendance, array $data, User $actor): Attendance
    {
        $tracked = ['checked_in_at', 'checked_out_at', 'worked_minutes', 'late_minutes', 'status'];

        [$corrected, $oldValues] = DB::transaction(function () use ($attendance, $data, $tracked): array {
            $attendance = Attendance::query()->lockForUpdate()->findOrFail($attendance->id);
            $before = $this->snapshot($attendance, $tracked);

            $checkIn = isset($data['checked_in_at']) ? Carbon::parse($data['checked_in_at']) : null;
            $checkOut = isset($data['checked_out_at']) ? Carbon::parse($data['checked_out_at']) : null;

            $attendance->update([
                ...$data,
                'worked_minutes' => $checkIn && $checkOut ? (int) $checkIn->diffInMinutes($checkOut) : 0,
                'updated_by' => $data['updated_by'] ?? null,
            ]);

            return [$attendance->refresh(), $before];
        });

        AttendanceCorrected::dispatch(
            $corrected,
            $actor,
            $oldValues,
            $this->snapshot($corrected, $tracked),
            (string) ($data['correction_reason'] ?? ''),
        );

        return $corrected;
    }

    /**
     * @param  list<string>  $columns
     * @return array<string, mixed>
     */
    private function snapshot(Attendance $attendance, array $columns): array
    {
        $values = [];

        foreach ($columns as $column) {
            $value = $attendance->getAttribute($column);
            // DateTimeInterface, not Carbon: this application uses CarbonImmutable, which
            // is not a subclass of the mutable Carbon.
            $values[$column] = match (true) {
                $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
                $value instanceof BackedEnum => $value->value,
                default => $value,
            };
        }

        return $values;
    }
}
