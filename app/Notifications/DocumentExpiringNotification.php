<?php

namespace App\Notifications;

use App\Models\EmployeeDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DocumentExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public EmployeeDocument $document) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Dokumen akan kedaluwarsa',
            'message' => "{$this->document->name} kedaluwarsa pada {$this->document->expires_at?->toDateString()}.",
            'url' => "/employees/{$this->document->employee_id}",
        ];
    }
}
