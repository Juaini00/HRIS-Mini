<?php

namespace App\Providers;

use App\Events\AnnouncementPublished;
use App\Events\AttendanceCorrected;
use App\Events\EmployeeCreated;
use App\Events\LeaveRequestCancelled;
use App\Events\LeaveRequestReviewed;
use App\Events\LeaveRequestSubmitted;
use App\Events\PayrollPublished;
use App\Listeners\RecordDomainAudit;
use App\Listeners\SendLeaveNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Wires domain events to their listeners.
 *
 * Listeners are registered explicitly rather than discovered, so the whole map of
 * "what happens when X occurs" is readable in one place.
 */
class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(EmployeeCreated::class, [RecordDomainAudit::class, 'employeeCreated']);

        Event::listen(LeaveRequestSubmitted::class, [RecordDomainAudit::class, 'leaveSubmitted']);
        Event::listen(LeaveRequestSubmitted::class, [SendLeaveNotifications::class, 'submitted']);

        Event::listen(LeaveRequestReviewed::class, [RecordDomainAudit::class, 'leaveReviewed']);
        Event::listen(LeaveRequestReviewed::class, [SendLeaveNotifications::class, 'reviewed']);

        Event::listen(LeaveRequestCancelled::class, [RecordDomainAudit::class, 'leaveCancelled']);
        Event::listen(LeaveRequestCancelled::class, [SendLeaveNotifications::class, 'cancelled']);

        Event::listen(AttendanceCorrected::class, [RecordDomainAudit::class, 'attendanceCorrected']);
        Event::listen(PayrollPublished::class, [RecordDomainAudit::class, 'payrollPublished']);
        Event::listen(AnnouncementPublished::class, [RecordDomainAudit::class, 'announcementPublished']);
    }
}
