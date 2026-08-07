<?php
namespace Database\Factories;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;
class LeaveRequestFactory extends Factory { protected $model=LeaveRequest::class; public function definition(): array { $start=fake()->dateTimeBetween('now','+3 months'); return ['employee_id'=>Employee::factory(),'leave_type_id'=>LeaveType::factory(),'start_date'=>$start,'end_date'=>$start,'days'=>1,'reason'=>fake()->sentence(),'status'=>'pending']; } }
