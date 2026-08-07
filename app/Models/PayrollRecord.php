<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One payslip's worth of figures for a single employee in a single period.
 *
 * Amounts are snapshots: editing a master salary component later must never change
 * a payslip that has already been generated.
 *
 * @property int $id
 * @property int $payroll_period_id
 * @property int $employee_id
 * @property string $basic_salary
 * @property string $earnings
 * @property string $deductions
 * @property string $total_earnings
 * @property string $total_deductions
 * @property string $gross_salary
 * @property string $net_salary
 * @property string $working_days
 * @property string $present_days
 * @property string $paid_leave_days
 * @property string $unpaid_leave_days
 * @property string $absent_days
 * @property int $overtime_minutes
 * @property array<string, mixed>|null $breakdown
 * @property Carbon|null $generated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PayrollPeriod $period
 * @property-read Employee $employee
 */
class PayrollRecord extends Model
{
    protected $fillable = [
        'payroll_period_id', 'employee_id', 'basic_salary', 'earnings', 'deductions',
        'total_earnings', 'total_deductions', 'gross_salary', 'net_salary',
        'working_days', 'present_days', 'paid_leave_days', 'unpaid_leave_days',
        'absent_days', 'overtime_minutes', 'breakdown', 'generated_at',
    ];

    /** @return BelongsTo<PayrollPeriod, $this> */
    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return HasMany<PayrollRecordItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(PayrollRecordItem::class);
    }

    /** @return HasMany<PayrollAdjustment, $this> */
    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'earnings' => 'decimal:2',
            'deductions' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'working_days' => 'decimal:2',
            'present_days' => 'decimal:2',
            'paid_leave_days' => 'decimal:2',
            'unpaid_leave_days' => 'decimal:2',
            'absent_days' => 'decimal:2',
            'overtime_minutes' => 'integer',
            'breakdown' => 'array',
            'generated_at' => 'datetime',
        ];
    }
}
