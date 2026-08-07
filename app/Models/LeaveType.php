<?php

namespace App\Models;

use Database\Factories\LeaveTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Leave policy rules are deliberately configurable demo defaults — statutory
 * entitlements vary by jurisdiction and this application does not model any
 * specific labour law.
 *
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property int $annual_quota
 * @property bool $is_paid
 * @property bool $requires_attachment
 * @property int|null $max_consecutive_days
 * @property int $min_notice_days
 * @property bool $allows_negative_balance
 * @property bool $carry_forward_enabled
 * @property int|null $max_carry_forward_days
 * @property string $color
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class LeaveType extends Model
{
    /** @use HasFactory<LeaveTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'description', 'annual_quota', 'is_paid', 'requires_attachment',
        'max_consecutive_days', 'min_notice_days', 'allows_negative_balance',
        'carry_forward_enabled', 'max_carry_forward_days', 'color', 'is_active',
    ];

    /** @return HasMany<LeaveBalance, $this> */
    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /** @return HasMany<LeaveRequest, $this> */
    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'annual_quota' => 'integer',
            'is_paid' => 'boolean',
            'requires_attachment' => 'boolean',
            'max_consecutive_days' => 'integer',
            'min_notice_days' => 'integer',
            'allows_negative_balance' => 'boolean',
            'carry_forward_enabled' => 'boolean',
            'max_carry_forward_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
