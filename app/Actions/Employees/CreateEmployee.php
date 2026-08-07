<?php

namespace App\Actions\Employees;

use App\Enums\EmploymentStatus;
use App\Events\EmployeeCreated;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates the login identity and the HR record together.
 */
class CreateEmployee
{
    /**
     * How many times to retry when two requests race for the same employee number.
     */
    private const MAX_ATTEMPTS = 5;

    public function __construct(private GenerateEmployeeNumber $numbers) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, User $actor): Employee
    {
        $explicitNumber = filled($data['employee_number'] ?? null) ? (string) $data['employee_number'] : null;

        for ($attempt = 1; ; $attempt++) {
            try {
                $employee = $this->create($data, $actor, $explicitNumber);
                EmployeeCreated::dispatch($employee, $actor);

                return $employee;
            } catch (UniqueConstraintViolationException $exception) {
                // An operator-supplied number that collides is a validation problem, not a
                // race — surface it instead of silently renumbering their input.
                if ($explicitNumber !== null || $attempt >= self::MAX_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function create(array $data, User $actor, ?string $explicitNumber): Employee
    {
        return DB::transaction(function () use ($data, $actor, $explicitNumber): Employee {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                // Use the operator-supplied password when given; otherwise store an
                // unknowable placeholder and let the employee set one via the reset flow.
                'password' => Hash::make(filled($data['password'] ?? null) ? $data['password'] : Str::password(32)),
                'role' => $data['role'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $joinedAt = $data['joined_at'];

            $employee = $user->employee()->create([
                ...Arr::except($data, ['name', 'email', 'role', 'employee_number', 'password']),
                'employee_number' => $explicitNumber ?? $this->numbers->next((int) Carbon::parse($joinedAt)->year),
                'employment_status' => $data['employment_status'] ?? EmploymentStatus::Active,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $employee->salaryHistories()->create([
                'amount' => $data['basic_salary'],
                'effective_from' => $joinedAt,
                'created_by' => $actor->id,
                'notes' => 'Initial salary',
            ]);

            return $employee;
        });
    }
}
