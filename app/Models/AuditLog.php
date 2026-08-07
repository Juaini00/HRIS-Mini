<?php

namespace App\Models;

use App\Actions\Audit\WriteAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Append-only record of important actions.
 *
 * Writes go through {@see WriteAuditLog}, which is responsible for
 * redacting secrets before anything lands here.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $event
 * @property string|null $event_category
 * @property string|null $description
 * @property string|null $auditable_type
 * @property int|null $auditable_id
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property array<string, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 */
class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'event', 'event_category', 'description', 'auditable_type',
        'auditable_id', 'old_values', 'new_values', 'metadata', 'ip_address', 'user_agent',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];
    }
}
