<?php

namespace App\Notifications;

use App\Models\PayrollPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PayrollPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(public PayrollPeriod $period) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toArray(object $notifiable): array { return ['title' => 'Payslip tersedia', 'message' => "Payslip {$this->period->name} telah dipublikasikan.", 'url' => '/payroll']; }
}
