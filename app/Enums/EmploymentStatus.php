<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EmploymentStatus: string
{
    use HasOptions;

    case Active = 'active';
    case Probation = 'probation';
    case OnLeave = 'on_leave';
    case Suspended = 'suspended';
    case Resigned = 'resigned';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Probation => 'Probation',
            self::OnLeave => 'On Leave',
            self::Suspended => 'Suspended',
            self::Resigned => 'Resigned',
            self::Terminated => 'Terminated',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Probation, self::OnLeave => 'warning',
            self::Suspended => 'destructive',
            self::Resigned, self::Terminated => 'muted',
        };
    }

    /**
     * Statuses that still count the employee as part of the active workforce.
     *
     * Used by payroll eligibility, headcount metrics, and absence processing.
     */
    public function isCurrentlyEmployed(): bool
    {
        return in_array($this, [self::Active, self::Probation, self::OnLeave], true);
    }

    /**
     * @return list<string>
     */
    public static function employedValues(): array
    {
        return array_values(array_map(
            fn (self $case): string => $case->value,
            array_filter(self::cases(), fn (self $case): bool => $case->isCurrentlyEmployed()),
        ));
    }
}
