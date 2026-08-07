<?php

namespace App\Events;

use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Raised after a leave request is approved or rejected.
 *
 * Approval and rejection share one event because every listener cares about the same
 * thing — the request reached a decision — and branches on {@see self::$decision} only
 * where the wording differs.
 */
class LeaveRequestReviewed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public LeaveRequest $leaveRequest,
        public User $reviewer,
        public LeaveRequestStatus $decision,
    ) {}

    public function wasApproved(): bool
    {
        return $this->decision === LeaveRequestStatus::Approved;
    }
}
