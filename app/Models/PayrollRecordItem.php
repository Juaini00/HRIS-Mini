<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRecordItem extends Model
{
    protected $fillable = ['payroll_record_id', 'salary_component_id', 'name', 'type', 'amount', 'is_manual', 'notes', 'created_by'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'is_manual' => 'boolean']; }
    public function record(): BelongsTo { return $this->belongsTo(PayrollRecord::class, 'payroll_record_id'); }
}
