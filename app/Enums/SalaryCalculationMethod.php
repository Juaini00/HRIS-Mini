<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum SalaryCalculationMethod: string
{
    use HasOptions;

    case Fixed = 'fixed';
    case PercentageOfBasic = 'percentage_of_basic';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed amount',
            self::PercentageOfBasic => 'Percentage of basic salary',
        };
    }
}
