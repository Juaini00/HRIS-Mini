<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\User;
use App\Support\Permissions;

class AttendanceCorrectionPolicy
{
    /**
     * An employee may only ask for a correction to their own attendance, and only while
     * they are still the person the record belongs to.
     */
    public function create(User $user, Attendance $attendance): bool
    {
        return $user->employee !== null && $user->employee->id === $attendance->employee_id;
    }

    public function view(User $user, AttendanceCorrection $correction): bool
    {
        return $user->can(Permissions::ATTENDANCE_CORRECT)
            || $correction->requested_by === $user->id;
    }

    /**
     * Reviewing is an HR action. Employees cannot approve their own request, which is
     * what the permission check already guarantees.
     */
    public function review(User $user): bool
    {
        return $user->can(Permissions::ATTENDANCE_CORRECT);
    }
}
