<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One balance row per employee, leave type, and year.
 *
 * `remaining` is derived rather than stored so it can never disagree with its parts.
 *
 * @property int $id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property int $year
 * @property string $entitled
 * @property string $carried_forward
 * @property string $used
 * @property string $pending
 * @property string $adjustment
 * @property Carbon|null $last_recalculated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read LeaveType $leaveType
 * @property-read Employee $employee
 */
class LeaveBalance extends Model
{
    protected $fillable = [
        'employee_id', 'leave_type_id', 'year', 'entitled', 'carried_forward',
        'used', 'pending', 'adjustment', 'last_recalculated_at',
    ];

    protected $appends = ['remaining'];

    /**
     * Days still available to book: everything granted, minus what is spent or reserved.
     */
    public function getRemainingAttribute(): float
    {
        return round(
            (float) $this->entitled
            + (float) $this->carried_forward
            + (float) $this->adjustment
            - (float) $this->used
            - (float) $this->pending,
            2,
        );
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<LeaveType, $this> */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'entitled' => 'decimal:2',
            'carried_forward' => 'decimal:2',
            'used' => 'decimal:2',
            'pending' => 'decimal:2',
            'adjustment' => 'decimal:2',
            'last_recalculated_at' => 'datetime',
        ];
    }
}
