<?php

namespace App\Console\Commands;

use App\Events\AnnouncementPublished;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use Illuminate\Console\Command;

class PublishScheduledAnnouncements extends Command
{
    protected $signature = 'nusahr:publish-announcements';

    protected $description = 'Publikasikan dan beri notifikasi untuk pengumuman terjadwal';

    public function handle(): int
    {
        Announcement::query()->whereNotNull('published_at')->where('published_at', '<=', now())->whereNull('notified_at')->each(function (Announcement $announcement): void {
            $users = User::query()->where('is_active', true)->where(function ($query) use ($announcement): void {
                $query->when($announcement->audience !== 'all', fn ($roles) => $roles->where('role', $announcement->audience));
                $query->when($announcement->department_id, fn ($departments) => $departments->whereHas('employee', fn ($employees) => $employees->where('department_id', $announcement->department_id)));
                $query->when($announcement->location_id, fn ($locations) => $locations->whereHas('employee', fn ($employees) => $employees->where('location_id', $announcement->location_id)));
            })->get();
            $users->each->notify(new AnnouncementPublishedNotification($announcement));
            $announcement->update(['notified_at' => now()]);
            AnnouncementPublished::dispatch($announcement);
        });

        return self::SUCCESS;
    }
}
