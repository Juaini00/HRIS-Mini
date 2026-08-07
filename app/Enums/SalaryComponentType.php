<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum SalaryComponentType: string
{
    use HasOptions;

    case Earning = 'earning';
    case Deduction = 'deduction';

    public function label(): string
    {
        return match ($this) {
            self::Earning => 'Earning',
            self::Deduction => 'Deduction',
        };
    }

    public function badge(): string
    {
        return $this === self::Earning ? 'success' : 'destructive';
    }
}
