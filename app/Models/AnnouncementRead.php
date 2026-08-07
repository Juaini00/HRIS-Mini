<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $announcement_id
 * @property int $user_id
 * @property Carbon|null $read_at
 * @property-read Announcement $announcement
 */
class AnnouncementRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['announcement_id', 'user_id', 'read_at'];

    /** @return BelongsTo<Announcement, $this> */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

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
        return ['read_at' => 'datetime'];
    }
}
