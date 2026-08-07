<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() || $user->role === UserRole::Manager;
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->isAdministrator() || $user->employee?->id === $employee->id || $employee->manager_id === $user->employee?->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->isAdministrator()
            && ($employee->user->role !== UserRole::SuperAdmin || $user->role === UserRole::SuperAdmin);
    }

    public function deactivate(User $user, Employee $employee): bool
    {
        return $user->isAdministrator()
            && $user->employee?->id !== $employee->id
            && ($employee->user->role !== UserRole::SuperAdmin || $user->role === UserRole::SuperAdmin);
    }

    public function viewSensitive(User $user, Employee $employee): bool
    {
        return $user->isAdministrator() || $user->employee?->id === $employee->id;
    }
}
