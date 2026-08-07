<?php

namespace App\Policies;

use App\Enums\LeaveStatus;
use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->isAdministrator() || $user->employee?->id === $leaveRequest->employee_id || $leaveRequest->employee->manager_id === $user->employee?->id;
    }

    public function review(User $user, LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->status === LeaveStatus::Pending
            && $user->employee?->id !== $leaveRequest->employee_id
            && ($user->isAdministrator() || $leaveRequest->employee->manager_id === $user->employee?->id);
    }

    public function cancel(User $user, LeaveRequest $leaveRequest): bool
    {
        return ($user->employee?->id === $leaveRequest->employee_id || $user->isAdministrator())
            && in_array($leaveRequest->status, [LeaveStatus::Pending, LeaveStatus::Approved], true)
            && $leaveRequest->start_date->isFuture();
    }
}
