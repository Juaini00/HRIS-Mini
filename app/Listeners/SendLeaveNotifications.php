<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\LeaveRequestCancelled;
use App\Events\LeaveRequestReviewed;
use App\Events\LeaveRequestSubmitted;
use App\Models\User;
use App\Notifications\LeaveReviewedNotification;
use App\Notifications\LeaveSubmittedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Turns leave events into user-facing notifications.
 *
 * Kept out of the action classes so a notification failure can never roll back the
 * balance movement that has already been committed.
 */
class SendLeaveNotifications
{
    public function submitted(LeaveRequestSubmitted $event): void
    {
        $request = $event->leaveRequest->loadMissing('employee.user', 'employee.manager.user', 'leaveType');

        Notification::send(
            $this->approversFor($request->employee->manager?->user),
            new LeaveSubmittedNotification($request),
        );
    }

    public function reviewed(LeaveRequestReviewed $event): void
    {
        $request = $event->leaveRequest->loadMissing('employee.user', 'leaveType');

        $request->employee->user->notify(new LeaveReviewedNotification($request));
    }

    public function cancelled(LeaveRequestCancelled $event): void
    {
        $request = $event->leaveRequest->loadMissing('employee.user', 'employee.manager.user', 'leaveType');
        $employeeUser = $request->employee->user;

        // Tell the employee only when someone else cancelled on their behalf.
        if ($employeeUser->isNot($event->actor)) {
            $employeeUser->notify(new LeaveReviewedNotification($request));
        }
    }

    /**
     * The direct manager plus every active HR administrator.
     *
     * @return Collection<int, User>
     */
    private function approversFor(?User $manager): Collection
    {
        $recipients = User::query()
            ->whereIn('role', [UserRole::SuperAdmin->value, UserRole::HrAdmin->value])
            ->where('is_active', true)
            ->get();

        if ($manager !== null) {
            $recipients->push($manager);
        }

        return $recipients->unique('id');
    }
}
