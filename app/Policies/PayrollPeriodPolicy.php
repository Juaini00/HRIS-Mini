<?php

namespace App\Policies;

use App\Enums\PayrollStatus;
use App\Models\PayrollPeriod;
use App\Models\User;

class PayrollPeriodPolicy
{
    public function manage(User $user, ?PayrollPeriod $period = null): bool { return $user->isAdministrator(); }
    public function view(User $user, PayrollPeriod $period): bool { return $user->isAdministrator() || ($period->status === PayrollStatus::Published && $user->employee !== null); }
}
