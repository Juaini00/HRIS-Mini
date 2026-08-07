<?php

namespace App\Models;

use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $department_id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property int $level
 * @property string|null $min_salary
 * @property string|null $max_salary
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Department|null $department
 */
class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use HasFactory;

    protected $fillable = ['department_id', 'name', 'code', 'description', 'level', 'min_salary', 'max_salary', 'is_active'];

    /**
     * Salary bands are administrative data and must never reach an employee or a
     * manager without `employees.manage-compensation`.
     */
    protected $hidden = ['min_salary', 'max_salary'];

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return HasMany<Employee, $this> */
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'min_salary' => 'decimal:2',
            'max_salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
