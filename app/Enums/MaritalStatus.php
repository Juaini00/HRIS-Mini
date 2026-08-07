<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum MaritalStatus: string
{
    use HasOptions;

    case Single = 'single';
    case Married = 'married';
    case Divorced = 'divorced';
    case Widowed = 'widowed';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single',
            self::Married => 'Married',
            self::Divorced => 'Divorced',
            self::Widowed => 'Widowed',
        };
    }
}
