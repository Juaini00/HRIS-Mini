<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $date
 * @property string $name
 * @property string|null $description
 * @property bool $is_recurring
 * @property int|null $location_id
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Holiday extends Model
{
    protected $fillable = ['date', 'name', 'description', 'is_recurring', 'location_id', 'is_active'];

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
