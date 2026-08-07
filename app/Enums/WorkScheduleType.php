<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum WorkScheduleType: string
{
    use HasOptions;

    case Office = 'office';
    case Remote = 'remote';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::Office => 'Office',
            self::Remote => 'Remote',
            self::Hybrid => 'Hybrid',
        };
    }
}
