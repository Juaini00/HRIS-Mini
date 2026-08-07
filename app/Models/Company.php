<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The single company this HRIS instance serves.
 *
 * Modelled as a table rather than config so HR can edit the profile, attendance
 * schedule, and payroll defaults from the settings screen.
 *
 * @property int $id
 * @property string $name
 * @property string|null $legal_name
 * @property string $code
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $city
 * @property string|null $province
 * @property string|null $postal_code
 * @property string $country
 * @property string|null $logo_path
 * @property string $timezone
 * @property string $currency
 * @property string $attendance_starts_at
 * @property string $attendance_ends_at
 * @property int $attendance_grace_minutes
 * @property int $default_annual_leave_days
 * @property int $payroll_cutoff_day
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Company extends Model
{
    protected $fillable = [
        'name', 'legal_name', 'code', 'email', 'phone', 'address', 'city', 'province',
        'postal_code', 'country', 'logo_path', 'timezone', 'currency',
        'attendance_starts_at', 'attendance_ends_at', 'attendance_grace_minutes',
        'default_annual_leave_days', 'payroll_cutoff_day', 'is_active',
    ];

    /**
     * The active company profile, or a sensible unsaved default so the app still
     * renders before the settings screen has ever been used.
     */
    public static function current(): self
    {
        return self::query()->where('is_active', true)->orderBy('id')->first()
            ?? new self(['name' => config('app.name'), 'code' => 'DEFAULT']);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_grace_minutes' => 'integer',
            'default_annual_leave_days' => 'integer',
            'payroll_cutoff_day' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
