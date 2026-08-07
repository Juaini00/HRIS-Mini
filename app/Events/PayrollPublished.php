<?php

namespace App\Events;

use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Raised once a payroll period is published and its payslips become visible.
 */
class PayrollPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PayrollPeriod $period,
        public User $publisher,
    ) {}
}
