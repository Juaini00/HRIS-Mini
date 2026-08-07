<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An office location. Latitude/longitude/radius are stored for a possible future
 * geofenced attendance feature but are not enforced in the MVP.
 *
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $address
 * @property string|null $city
 * @property string|null $province
 * @property string $country
 * @property string $timezone
 * @property string|null $latitude
 * @property string|null $longitude
 * @property int|null $attendance_radius_meters
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Location extends Model
{
    protected $fillable = [
        'name', 'code', 'address', 'city', 'province', 'country', 'timezone',
        'latitude', 'longitude', 'attendance_radius_meters', 'is_active',
    ];

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
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'attendance_radius_meters' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
