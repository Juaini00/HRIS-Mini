<?php
namespace Database\Factories;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;
class LeaveTypeFactory extends Factory { protected $model=LeaveType::class; public function definition(): array { return ['name'=>fake()->unique()->words(2,true),'annual_quota'=>12,'is_paid'=>true,'requires_attachment'=>false,'is_active'=>true]; } }
