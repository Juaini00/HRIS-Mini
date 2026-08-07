<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AnnouncementPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(public Announcement $announcement) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toArray(object $notifiable): array { return ['title' => $this->announcement->title, 'message' => str($this->announcement->body)->limit(120)->toString(), 'url' => '/announcements']; }
}
