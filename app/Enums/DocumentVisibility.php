<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum DocumentVisibility: string
{
    use HasOptions;

    case HrOnly = 'hr_only';
    case EmployeeAndHr = 'employee_and_hr';
    case InternalProfile = 'internal_profile';

    public function label(): string
    {
        return match ($this) {
            self::HrOnly => 'HR only',
            self::EmployeeAndHr => 'Employee and HR',
            self::InternalProfile => 'Internal profile',
        };
    }

    /**
     * Whether the owning employee is allowed to see the document themselves.
     */
    public function isVisibleToOwner(): bool
    {
        return $this !== self::HrOnly;
    }
}
