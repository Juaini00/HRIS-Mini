<?php

namespace App\Actions\Employees;

use App\Models\Employee;

/**
 * Produces readable, per-year employee numbers such as `EMP-2026-0001`.
 */
class GenerateEmployeeNumber
{
    private const PREFIX = 'EMP';

    private const SEQUENCE_LENGTH = 4;

    /**
     * The next unused number for the given year.
     *
     * This is a *candidate*, not a reservation: two concurrent callers can and will
     * receive the same value. The unique index on `employees.employee_number` is what
     * actually enforces uniqueness — callers retry on violation via
     * {@see CreateEmployee}. That keeps the generator free of table locks, which
     * matters because SQLite (used by the test suite) has no row-level locking.
     */
    public function next(?int $year = null): string
    {
        $year ??= (int) now()->year;
        $prefix = self::PREFIX.'-'.$year.'-';

        $highest = Employee::query()
            ->where('employee_number', 'like', $prefix.'%')
            ->orderByDesc('employee_number')
            ->value('employee_number');

        $sequence = $highest === null
            ? 1
            : ((int) substr($highest, strlen($prefix))) + 1;

        return $prefix.str_pad((string) $sequence, self::SEQUENCE_LENGTH, '0', STR_PAD_LEFT);
    }
}
