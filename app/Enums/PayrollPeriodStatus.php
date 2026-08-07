<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PayrollPeriodStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Processing = 'processing';
    case Generated = 'generated';
    case Published = 'published';
    case Closed = 'closed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Processing => 'Processing',
            self::Generated => 'Generated',
            self::Published => 'Published',
            self::Closed => 'Closed',
            self::Failed => 'Failed',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Draft => 'muted',
            self::Processing => 'info',
            self::Generated => 'warning',
            self::Published => 'success',
            self::Closed => 'muted',
            self::Failed => 'destructive',
        };
    }

    /**
     * Whether payroll figures may still be generated or recalculated.
     *
     * Published and closed periods hold historical snapshots and must stay frozen.
     */
    public function allowsRecalculation(): bool
    {
        return in_array($this, [self::Draft, self::Generated, self::Failed], true);
    }

    /**
     * Whether employees may see payslips for this period.
     */
    public function payslipsAreVisible(): bool
    {
        return in_array($this, [self::Published, self::Closed], true);
    }
}
