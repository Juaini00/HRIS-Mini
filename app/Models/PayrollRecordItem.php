<?php

namespace App\Models;

use App\Enums\SalaryComponentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single line on a payslip. Name and type are copied from the master component at
 * generation time so historical payslips stay stable.
 *
 * @property int $id
 * @property int $payroll_record_id
 * @property int|null $salary_component_id
 * @property string $name
 * @property SalaryComponentType $type
 * @property string $amount
 * @property bool $is_manual
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PayrollRecord $record
 */
class PayrollRecordItem extends Model
{
    protected $fillable = ['payroll_record_id', 'salary_component_id', 'name', 'type', 'amount', 'is_manual', 'notes', 'created_by'];

    /** @return BelongsTo<PayrollRecord, $this> */
    public function record(): BelongsTo
    {
        return $this->belongsTo(PayrollRecord::class, 'payroll_record_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'type' => SalaryComponentType::class,
            'is_manual' => 'boolean',
        ];
    }
}
