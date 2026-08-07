<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AttendanceSource: string
{
    use HasOptions;

    case SelfService = 'self_service';
    case HrEntry = 'hr_entry';
    case Import = 'import';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::SelfService => 'Self service',
            self::HrEntry => 'HR entry',
            self::Import => 'Import',
            self::System => 'System',
        };
    }
}
