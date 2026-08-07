<?php

namespace Database\Factories;

use App\Enums\DocumentCategory;
use App\Enums\DocumentVisibility;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDocument>
 */
class EmployeeDocumentFactory extends Factory
{
    protected $model = EmployeeDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word().'-'.fake()->word().'.pdf';

        return [
            'employee_id' => Employee::factory(),
            'uploaded_by' => User::factory(),
            'name' => $name,
            'title' => ucfirst(fake()->word()).' '.ucfirst(fake()->word()),
            'category' => fake()->randomElement(DocumentCategory::cases()),
            'path' => 'employee-documents/'.fake()->uuid().'.pdf',
            'original_filename' => $name,
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(20_000, 2_000_000),
            'visibility' => DocumentVisibility::HrOnly,
            'description' => fake()->optional()->sentence(),
            'expires_at' => fake()->optional()->dateTimeBetween('now', '+2 years'),
        ];
    }

    public function visibleToEmployee(): self
    {
        return $this->state(fn (): array => ['visibility' => DocumentVisibility::EmployeeAndHr]);
    }
}
