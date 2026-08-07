<?php

namespace App\Actions\Leave;

use App\Enums\LeaveStatus;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelLeaveRequest
{
    public function handle(LeaveRequest $leaveRequest, User $user, string $reason): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $user, $reason): LeaveRequest {
            $leaveRequest = LeaveRequest::query()->lockForUpdate()->findOrFail($leaveRequest->id);
            if (! in_array($leaveRequest->status, [LeaveStatus::Pending, LeaveStatus::Approved], true) || ! $leaveRequest->start_date->isFuture()) {
                throw ValidationException::withMessages(['reason' => 'Cuti tidak dapat dibatalkan.']);
            }
            if ($leaveRequest->leaveType->is_paid) {
                $balance = LeaveBalance::query()->where('employee_id', $leaveRequest->employee_id)->where('leave_type_id', $leaveRequest->leave_type_id)->where('year', $leaveRequest->start_date->year)->lockForUpdate()->firstOrFail();
                if ($leaveRequest->status === LeaveStatus::Pending) { $balance->decrement('pending', $leaveRequest->days); }
                if ($leaveRequest->status === LeaveStatus::Approved) { $balance->decrement('used', $leaveRequest->days); }
            }
            $leaveRequest->update(['status' => LeaveStatus::Cancelled, 'cancelled_at' => now(), 'cancelled_by' => $user->id, 'cancellation_reason' => $reason]);

            return $leaveRequest->refresh();
        });
    }
}
