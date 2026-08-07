<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AttendanceStatus: string
{
    use HasOptions;

    case Present = 'present';
    case Late = 'late';
    case Absent = 'absent';
    case OnLeave = 'leave';
    case Holiday = 'holiday';
    case Weekend = 'weekend';
    case Incomplete = 'incomplete';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Late => 'Late',
            self::Absent => 'Absent',
            self::OnLeave => 'On Leave',
            self::Holiday => 'Holiday',
            self::Weekend => 'Weekend',
            self::Incomplete => 'Incomplete',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Late, self::Incomplete => 'warning',
            self::Absent => 'destructive',
            self::OnLeave => 'info',
            self::Holiday, self::Weekend => 'muted',
        };
    }

    /**
     * Statuses that represent an explained non-working day.
     *
     * Absence processing must never overwrite these with `Absent`.
     */
    public function isExcused(): bool
    {
        return in_array($this, [self::OnLeave, self::Holiday, self::Weekend], true);
    }

    /**
     * Statuses where the employee actually worked, so payroll counts them as present.
     */
    public function isWorked(): bool
    {
        return in_array($this, [self::Present, self::Late, self::Incomplete], true);
    }
}
