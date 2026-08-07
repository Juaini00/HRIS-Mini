<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Small key/value store for operational settings that are not part of the company
 * profile (see {@see Company} for that).
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property bool $is_public
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'is_public'];

    /**
     * Read a setting without a query per lookup when several are needed together.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return self::query()->where('key', $key)->value('value') ?? $default;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }
}
