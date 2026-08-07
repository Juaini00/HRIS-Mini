<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LeaveSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LeaveRequest $leaveRequest) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toArray(object $notifiable): array
    {
        return ['title' => 'Pengajuan cuti baru', 'message' => "{$this->leaveRequest->employee->user->name} mengajukan {$this->leaveRequest->days} hari cuti.", 'url' => '/leave'];
    }
}
