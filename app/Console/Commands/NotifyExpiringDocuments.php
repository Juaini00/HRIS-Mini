<?php

namespace App\Console\Commands;

use App\Models\EmployeeDocument;
use App\Models\User;
use App\Notifications\DocumentExpiringNotification;
use Illuminate\Console\Command;

class NotifyExpiringDocuments extends Command
{
    protected $signature = 'nusahr:notify-expiring-documents';

    protected $description = 'Notify HR about employee documents expiring in 30 days';

    public function handle(): int
    {
        $admins = User::whereIn('role', ['super_admin', 'hr_admin'])->where('is_active', true)->get();
        EmployeeDocument::whereDate('expires_at', today()->addDays(30))->each(fn (EmployeeDocument $document) => $admins->each->notify(new DocumentExpiringNotification($document)));

        return self::SUCCESS;
    }
}
