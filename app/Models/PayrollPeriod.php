<?php

namespace App\Models;

use App\Enums\PayrollPeriodStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int|null $year
 * @property int|null $month
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property Carbon|null $payment_date
 * @property PayrollPeriodStatus $status
 * @property Carbon|null $generated_at
 * @property int|null $generated_by
 * @property Carbon|null $published_at
 * @property int|null $published_by
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PayrollPeriod extends Model
{
    protected $fillable = [
        'name', 'year', 'month', 'starts_on', 'ends_on', 'payment_date', 'status',
        'generated_at', 'generated_by', 'published_at', 'published_by', 'notes',
    ];

    protected $attributes = ['status' => PayrollPeriodStatus::Draft->value];

    /** @return HasMany<PayrollRecord, $this> */
    public function records(): HasMany
    {
        return $this->hasMany(PayrollRecord::class);
    }

    /** @return BelongsTo<User, $this> */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /** @return BelongsTo<User, $this> */
    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
            'payment_date' => 'date:Y-m-d',
            'status' => PayrollPeriodStatus::class,
            'generated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
