<?php

namespace App\Events;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Raised when HR changes an attendance record.
 *
 * Carries the values from before the change so the audit listener can record what was
 * altered without having to re-query a row that no longer holds the old data.
 */
class AttendanceCorrected
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function __construct(
        public Attendance $attendance,
        public User $actor,
        public array $oldValues,
        public array $newValues,
        public string $reason,
    ) {}
}
