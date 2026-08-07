<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit trail of every change made to an attendance record, plus the optional
 * employee-initiated correction request workflow.
 *
 * @property int $id
 * @property int $attendance_id
 * @property int $requested_by
 * @property int|null $reviewed_by
 * @property string $status
 * @property string $reason
 * @property string|null $review_notes
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Attendance $attendance
 */
class AttendanceCorrection extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'attendance_id', 'requested_by', 'reviewed_by', 'status', 'reason',
        'review_notes', 'old_values', 'new_values', 'reviewed_at',
    ];

    /** @return BelongsTo<Attendance, $this> */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }
}
