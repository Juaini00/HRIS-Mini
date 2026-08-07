<?php

namespace App\Policies;

use App\Enums\PayrollPeriodStatus;
use App\Models\PayrollRecord;
use App\Models\User;

class PayrollRecordPolicy
{
    public function view(User $user, PayrollRecord $record): bool
    {
        return $user->isAdministrator() || ($record->period->status === PayrollPeriodStatus::Published && $user->employee?->id === $record->employee_id);
    }
}
