<?php

namespace App\Models;

use App\Enums\AnnouncementAudienceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * One targeting row for an announcement: a department, an employment type, or a
 * single employee. Absent rows mean "everyone", matching
 * {@see AnnouncementAudienceType::All}.
 *
 * @property int $id
 * @property int $announcement_id
 * @property string $audienceable_type
 * @property int $audienceable_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Announcement $announcement
 */
class AnnouncementAudience extends Model
{
    protected $fillable = ['announcement_id', 'audienceable_type', 'audienceable_id'];

    /** @return BelongsTo<Announcement, $this> */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    /** @return MorphTo<Model, $this> */
    public function audienceable(): MorphTo
    {
        return $this->morphTo();
    }
}
