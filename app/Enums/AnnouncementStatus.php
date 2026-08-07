<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum AnnouncementStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft => 'muted',
            self::Scheduled => 'info',
            self::Published => 'success',
            self::Archived => 'warning',
        };
    }
}
