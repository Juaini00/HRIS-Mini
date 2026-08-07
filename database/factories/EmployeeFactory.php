<?php
namespace Database\Factories;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
class EmployeeFactory extends Factory { protected $model=Employee::class; public function definition(): array { return ['user_id'=>User::factory(),'employee_number'=>fake()->unique()->numerify('NSH-#####'),'department_id'=>Department::factory(),'position_id'=>fn(array $attributes)=>Position::factory()->create(['department_id'=>$attributes['department_id']])->id,'joined_at'=>fake()->dateTimeBetween('-5 years','-1 month'),'basic_salary'=>fake()->numberBetween(5_000_000,20_000_000)]; } }
