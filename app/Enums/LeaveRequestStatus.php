<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LeaveRequestStatus: string
{
    use HasOptions;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'destructive',
            self::Cancelled => 'muted',
        };
    }

    /**
     * Statuses that still hold a claim on the employee's leave balance.
     */
    public function reservesBalance(): bool
    {
        return in_array($this, [self::Pending, self::Approved], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Rejected, self::Cancelled], true);
    }
}
