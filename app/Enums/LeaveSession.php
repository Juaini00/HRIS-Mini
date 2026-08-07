<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LeaveSession: string
{
    use HasOptions;

    case FullDay = 'full_day';
    case FirstHalf = 'first_half';
    case SecondHalf = 'second_half';

    public function label(): string
    {
        return match ($this) {
            self::FullDay => 'Full day',
            self::FirstHalf => 'First half',
            self::SecondHalf => 'Second half',
        };
    }

    /**
     * Fraction of a working day this session consumes from the leave balance.
     */
    public function dayFraction(): float
    {
        return $this === self::FullDay ? 1.0 : 0.5;
    }
}
