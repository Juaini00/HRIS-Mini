<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()).' Leave',
            'annual_quota' => 12,
            'is_paid' => true,
            'requires_attachment' => false,
            'min_notice_days' => 0,
            'is_active' => true,
        ];
    }
}
