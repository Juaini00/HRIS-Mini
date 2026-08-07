<?php

namespace App\Models;

use App\Enums\SalaryComponentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A manual earning or deduction an HR user applies to one payroll record.
 *
 * Kept separate from `payroll_record_items` so a recalculation can safely wipe and
 * rebuild the computed items without losing the manual entries.
 *
 * @property int $id
 * @property int $payroll_record_id
 * @property string $name
 * @property SalaryComponentType $type
 * @property string $amount
 * @property string|null $reason
 * @property int $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PayrollRecord $payrollRecord
 */
class PayrollAdjustment extends Model
{
    protected $fillable = ['payroll_record_id', 'name', 'type', 'amount', 'reason', 'created_by'];

    /** @return BelongsTo<PayrollRecord, $this> */
    public function payrollRecord(): BelongsTo
    {
        return $this->belongsTo(PayrollRecord::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'type' => SalaryComponentType::class,
        ];
    }
}
