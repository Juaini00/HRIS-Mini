<?php

namespace App\Listeners;

use App\Events\AnnouncementPublished;
use App\Events\AttendanceCorrected;
use App\Events\EmployeeCreated;
use App\Events\LeaveRequestCancelled;
use App\Events\LeaveRequestReviewed;
use App\Events\LeaveRequestSubmitted;
use App\Events\PayrollPublished;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Writes an audit entry for every domain event.
 *
 * Centralising this here is what keeps the audit trail consistent: adding a new event
 * means adding one method, rather than remembering to sprinkle a log call through
 * whichever controller happened to trigger it.
 */
class RecordDomainAudit
{
    public function __construct(private Request $request) {}

    public function employeeCreated(EmployeeCreated $event): void
    {
        $this->write('employee.created', 'employee', $event->employee, $event->actor->id,
            "Created employee {$event->employee->employee_number}",
            newValues: ['employee_number' => $event->employee->employee_number],
        );
    }

    public function leaveSubmitted(LeaveRequestSubmitted $event): void
    {
        $this->write('leave.submitted', 'leave', $event->leaveRequest, $event->leaveRequest->submitted_by,
            "Submitted leave request {$event->leaveRequest->request_number}",
            newValues: ['days' => $event->leaveRequest->days, 'status' => $event->leaveRequest->status->value],
        );
    }

    public function leaveReviewed(LeaveRequestReviewed $event): void
    {
        $verb = $event->wasApproved() ? 'approved' : 'rejected';

        $this->write("leave.{$verb}", 'leave', $event->leaveRequest, $event->reviewer->id,
            'Leave request '.$verb,
            newValues: ['status' => $event->decision->value],
        );
    }

    public function leaveCancelled(LeaveRequestCancelled $event): void
    {
        $this->write('leave.cancelled', 'leave', $event->leaveRequest, $event->actor->id,
            'Leave request cancelled',
            newValues: ['reason' => $event->reason],
        );
    }

    public function attendanceCorrected(AttendanceCorrected $event): void
    {
        $this->write('attendance.corrected', 'attendance', $event->attendance, $event->actor->id,
            'Attendance record corrected',
            oldValues: $event->oldValues,
            newValues: [...$event->newValues, 'reason' => $event->reason],
        );
    }

    public function payrollPublished(PayrollPublished $event): void
    {
        $this->write('payroll.published', 'payroll', $event->period, $event->publisher->id,
            "Published payroll period {$event->period->name}",
            newValues: ['status' => $event->period->status->value],
        );
    }

    public function announcementPublished(AnnouncementPublished $event): void
    {
        $this->write('announcement.published', 'announcement', $event->announcement, $event->announcement->author_id,
            "Published announcement: {$event->announcement->title}",
        );
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function write(
        string $event,
        string $category,
        Model $subject,
        ?int $actorId,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        AuditLog::create([
            'user_id' => $actorId,
            'event' => $event,
            'event_category' => $category,
            'description' => $description,
            'auditable_type' => $subject->getMorphClass(),
            'auditable_id' => $subject->getKey(),
            'old_values' => $oldValues === null ? null : $this->redact($oldValues),
            'new_values' => $newValues === null ? null : $this->redact($newValues),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }

    /**
     * Strip anything confidential before it reaches the audit trail.
     *
     * An audit log that records salaries or bank details becomes the leak it was meant
     * to detect.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function redact(array $values): array
    {
        $sensitive = ['password', 'bank_account', 'basic_salary', 'salary', 'amount', 'tax_number', 'token', 'secret'];

        foreach ($values as $key => $value) {
            if (in_array($key, $sensitive, true)) {
                $values[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $values[$key] = $this->redact($value);
            }
        }

        return $values;
    }
}
