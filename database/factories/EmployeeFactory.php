<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'employee_number' => fake()->unique()->numerify('NSH-#####'),
            'department_id' => Department::factory(),
            'position_id' => fn (array $attributes) => Position::factory()
                ->create(['department_id' => $attributes['department_id']])
                ->getKey(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'joined_at' => fake()->dateTimeBetween('-5 years', '-1 month'),
            'employment_status' => EmploymentStatus::Active,
            'basic_salary' => fake()->numberBetween(5_000_000, 20_000_000),
        ];
    }
}
