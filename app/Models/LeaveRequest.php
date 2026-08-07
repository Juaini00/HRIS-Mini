<?php

namespace App\Models;

use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveSession;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $request_number
 * @property int $employee_id
 * @property int $leave_type_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property LeaveSession $start_session
 * @property LeaveSession $end_session
 * @property string $days
 * @property string|null $reason
 * @property string|null $attachment_path
 * @property LeaveRequestStatus $status
 * @property int|null $current_approver_id
 * @property int|null $submitted_by
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property int|null $rejected_by
 * @property Carbon|null $rejected_at
 * @property string|null $rejection_reason
 * @property int|null $cancelled_by
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $review_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Employee $employee
 * @property-read LeaveType $leaveType
 */
class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'request_number', 'employee_id', 'leave_type_id', 'start_date', 'end_date',
        'start_session', 'end_session', 'days', 'reason', 'attachment_path', 'status',
        'current_approver_id', 'submitted_by', 'approved_by', 'approved_at', 'rejected_by',
        'rejected_at', 'rejection_reason', 'cancelled_by', 'cancelled_at',
        'cancellation_reason', 'reviewed_by', 'reviewed_at', 'review_notes', 'duration_type',
    ];

    protected $attributes = ['status' => LeaveRequestStatus::Pending->value];

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

    /** @return BelongsTo<User, $this> */
    public function currentApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_approver_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'start_session' => LeaveSession::class,
            'end_session' => LeaveSession::class,
            'days' => 'decimal:2',
            'status' => LeaveRequestStatus::class,
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
