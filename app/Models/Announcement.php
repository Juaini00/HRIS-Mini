<?php

namespace App\Models;

use App\Enums\AnnouncementAudienceType;
use App\Enums\AnnouncementStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $author_id
 * @property string $title
 * @property string|null $slug
 * @property string|null $summary
 * @property string $body
 * @property AnnouncementStatus $status
 * @property AnnouncementAudienceType $audience_type
 * @property string $audience
 * @property int|null $department_id
 * @property int|null $location_id
 * @property bool $is_pinned
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $notified_at
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $author
 */
class Announcement extends Model
{
    protected $fillable = [
        'author_id', 'title', 'slug', 'summary', 'body', 'status', 'audience_type',
        'audience', 'department_id', 'location_id', 'is_pinned', 'published_at',
        'expires_at', 'notified_at', 'updated_by',
    ];

    /**
     * Announcements an employee is currently allowed to read: published, already live,
     * and not expired.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('status', AnnouncementStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(fn (Builder $inner) => $inner->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return HasMany<AnnouncementRead, $this> */
    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    /** @return HasMany<AnnouncementAudience, $this> */
    public function audiences(): HasMany
    {
        return $this->hasMany(AnnouncementAudience::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $announcement): void {
            if ($announcement->slug === null && $announcement->title !== '') {
                $announcement->slug = Str::slug($announcement->title).'-'.Str::lower(Str::random(6));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AnnouncementStatus::class,
            'audience_type' => AnnouncementAudienceType::class,
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }
}
