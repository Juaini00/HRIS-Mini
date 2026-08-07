<?php

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property int|null $parent_id
 * @property int|null $manager_id
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read self|null $parent
 * @property-read Employee|null $manager
 */
class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'parent_id', 'manager_id', 'is_active', 'created_by', 'updated_by'];

    /**
     * Every department at or below this one, so a department filter can include sub-teams.
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $collected = [];
        $frontier = [$this->id];

        while (count($frontier) > 0) {
            $children = self::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            $frontier = array_values(array_diff($children, $collected, [$this->id]));
            $collected = array_merge($collected, $frontier);
        }

        return $collected;
    }

    /**
     * Reject a parent that would close a loop in the department tree.
     */
    public function wouldCreateCycle(?int $parentId): bool
    {
        if ($parentId === null) {
            return false;
        }

        return $parentId === $this->id || in_array($parentId, $this->descendantIds(), true);
    }

    /** @return BelongsTo<Department, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Department, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    /** @return HasMany<Position, $this> */
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
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
        return ['is_active' => 'boolean'];
    }
}
