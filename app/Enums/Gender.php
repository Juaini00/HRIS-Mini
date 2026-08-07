<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum Gender: string
{
    use HasOptions;

    case Male = 'male';
    case Female = 'female';
    case Undisclosed = 'undisclosed';

    public function label(): string
    {
        return match ($this) {
            self::Male => 'Male',
            self::Female => 'Female',
            self::Undisclosed => 'Prefer not to say',
        };
    }
}
