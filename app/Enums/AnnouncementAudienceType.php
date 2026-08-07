<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AnnouncementAudienceType: string
{
    use HasOptions;

    case All = 'all';
    case Departments = 'departments';
    case EmploymentTypes = 'employment_types';
    case Employees = 'employees';

    public function label(): string
    {
        return match ($this) {
            self::All => 'All employees',
            self::Departments => 'Selected departments',
            self::EmploymentTypes => 'Selected employment types',
            self::Employees => 'Selected employees',
        };
    }

    /**
     * Whether this audience needs rows in `announcement_audiences` to be meaningful.
     */
    public function requiresTargets(): bool
    {
        return $this !== self::All;
    }
}
