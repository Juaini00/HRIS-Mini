<?php
namespace Database\Factories;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
class PositionFactory extends Factory { protected $model=Position::class; public function definition(): array { return ['department_id'=>Department::factory(),'name'=>fake()->jobTitle(),'is_active'=>true]; } }
