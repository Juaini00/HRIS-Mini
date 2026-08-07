<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SalaryComponent extends Model
{
    protected $fillable = ['name', 'type', 'calculation_type', 'value', 'is_taxable', 'is_active'];
    protected function casts(): array { return ['value' => 'decimal:4', 'is_taxable' => 'boolean', 'is_active' => 'boolean']; }
    public function employees(): BelongsToMany { return $this->belongsToMany(Employee::class, 'employee_salary_components')->withPivot(['override_value', 'effective_from', 'effective_to'])->withTimestamps(); }
}
