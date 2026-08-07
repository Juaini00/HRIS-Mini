<?php

namespace App\Events;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Raised after an employee and their login identity are committed together.
 */
class EmployeeCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public User $actor,
    ) {}
}
