<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case HrAdmin = 'hr_admin';
    case Manager = 'manager';
    case Employee = 'employee';

    public function canManagePeople(): bool
    {
        return in_array($this, [self::SuperAdmin, self::HrAdmin], true);
    }

    public function canManagePayroll(): bool
    {
        return in_array($this, [self::SuperAdmin, self::HrAdmin], true);
    }
}
