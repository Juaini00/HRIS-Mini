<?php

namespace App\Events;

use App\Models\Announcement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Raised when an announcement becomes visible to its audience, whether published
 * immediately or promoted from a schedule by the scheduler.
 */
class AnnouncementPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Announcement $announcement) {}
}
