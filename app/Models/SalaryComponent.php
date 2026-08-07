<?php

namespace App\Models;

use App\Enums\SalaryCalculationMethod;
use App\Enums\SalaryComponentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property SalaryComponentType $type
 * @property string $calculation_type
 * @property string $value
 * @property bool $is_taxable
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SalaryComponent extends Model
{
    protected $fillable = ['name', 'code', 'description', 'type', 'calculation_type', 'value', 'is_taxable', 'is_active'];

    /**
     * Whether this component is a percentage of basic salary rather than a flat amount.
     *
     * Accepts the legacy `percentage` value alongside the enum's
     * {@see SalaryCalculationMethod::PercentageOfBasic} so existing rows keep working.
     */
    public function isPercentageBased(): bool
    {
        return in_array($this->calculation_type, ['percentage', SalaryCalculationMethod::PercentageOfBasic->value], true);
    }

    /** @return BelongsToMany<Employee, $this> */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_salary_components')
            ->withPivot(['override_value', 'effective_from', 'effective_to'])
            ->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'type' => SalaryComponentType::class,
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
