<?php

namespace App\Models;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Enums\WorkMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One attendance record per employee per calendar date.
 *
 * The composite unique index on (employee_id, date) is what actually guarantees that
 * invariant under concurrency; the application checks are for friendly errors only.
 *
 * @property int $id
 * @property int $employee_id
 * @property Carbon $date
 * @property Carbon|null $checked_in_at
 * @property Carbon|null $checked_out_at
 * @property int $worked_minutes
 * @property int $break_minutes
 * @property int $late_minutes
 * @property int $overtime_minutes
 * @property AttendanceStatus $status
 * @property WorkMode $work_mode
 * @property AttendanceSource $source
 * @property int|null $location_id
 * @property string|null $check_in_notes
 * @property string|null $check_out_notes
 * @property string|null $correction_reason
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee $employee
 */
class Attendance extends Model
{
    protected $fillable = [
        'employee_id', 'date', 'checked_in_at', 'checked_out_at', 'worked_minutes',
        'break_minutes', 'late_minutes', 'overtime_minutes', 'status', 'work_mode',
        'source', 'location_id', 'check_in_notes', 'check_out_notes',
        'correction_reason', 'created_by', 'updated_by',
    ];

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return HasMany<AttendanceCorrection, $this> */
    public function corrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'worked_minutes' => 'integer',
            'break_minutes' => 'integer',
            'late_minutes' => 'integer',
            'overtime_minutes' => 'integer',
            'status' => AttendanceStatus::class,
            'work_mode' => WorkMode::class,
            'source' => AttendanceSource::class,
        ];
    }
}
