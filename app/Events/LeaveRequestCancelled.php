<?php

namespace App\Events;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Raised after a cancellation releases or restores the affected leave balance.
 */
class LeaveRequestCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public LeaveRequest $leaveRequest,
        public User $actor,
        public string $reason,
    ) {}
}
