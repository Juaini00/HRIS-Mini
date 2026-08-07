<?php
namespace App\Policies;
use App\Enums\PayrollStatus;
use App\Models\PayrollRecord;
use App\Models\User;
class PayrollRecordPolicy { public function view(User $user, PayrollRecord $record): bool { return $user->isAdministrator() || ($record->period->status === PayrollStatus::Published && $user->employee?->id === $record->employee_id); } }
