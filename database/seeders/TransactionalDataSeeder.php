<?php

namespace Database\Seeders;

use App\Enums\AnnouncementAudienceType;
use App\Enums\AnnouncementStatus;
use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\LeaveSession;
use App\Enums\UserRole;
use App\Enums\WorkMode;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Day-to-day records: attendance, leave, payroll, announcements, and the audit trail.
 *
 * Written with bulk inserts rather than model saves — roughly 60 days x 46 employees of
 * attendance alone would otherwise be thousands of individual round trips.
 */
class TransactionalDataSeeder extends Seeder
{
    private const ATTENDANCE_DAYS = 60;

    public function run(): void
    {
        fake()->seed(20260807);

        $employees = Employee::query()->currentlyEmployed()->with('user')->get();

        if ($employees->isEmpty()) {
            return;
        }

        $this->demoHoliday();
        $this->attendance($employees);
        $this->leaveRequests($employees);
        $this->reconcileAttendanceWithLeave();
        $this->announcements($employees);
        $this->auditTrail($employees);
    }

    /**
     * A holiday inside the attendance window.
     *
     * The fixed calendar holidays drift out of the rolling 60-day window depending on
     * when the demo is seeded, which would leave the `holiday` status with no examples.
     */
    private function demoHoliday(): void
    {
        $date = today()->subDays(20);

        if ($date->isWeekend()) {
            $date = $date->subDays(2);
        }

        Holiday::updateOrCreate(['date' => $date->toDateString()], [
            'name' => 'Company Anniversary',
            'description' => 'Demo company holiday.',
            'is_recurring' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Mark days covered by approved leave so attendance stops reading as unexplained absence.
     *
     * Runs after both attendance and leave exist; a single UPDATE ... FROM keeps it to one
     * round trip instead of a query per employee per day.
     */
    private function reconcileAttendanceWithLeave(): void
    {
        DB::table('attendances')
            ->whereIn('status', [AttendanceStatus::Absent->value, AttendanceStatus::Present->value, AttendanceStatus::Late->value])
            ->whereExists(fn ($query) => $query
                ->select(DB::raw(1))
                ->from('leave_requests')
                ->whereColumn('leave_requests.employee_id', 'attendances.employee_id')
                ->where('leave_requests.status', LeaveRequestStatus::Approved->value)
                ->whereColumn('leave_requests.start_date', '<=', 'attendances.date')
                ->whereColumn('leave_requests.end_date', '>=', 'attendances.date'))
            ->update([
                'status' => AttendanceStatus::OnLeave->value,
                'checked_in_at' => null,
                'checked_out_at' => null,
                'worked_minutes' => 0,
                'late_minutes' => 0,
                'overtime_minutes' => 0,
                'source' => AttendanceSource::System->value,
            ]);
    }

    /**
     * Sixty days of attendance with a believable mix of outcomes.
     *
     * Weekends and public holidays are recorded explicitly so absence processing and
     * reports can tell "not expected at work" apart from "did not show up".
     *
     * @param  Collection<int, Employee>  $employees
     */
    private function attendance(Collection $employees): void
    {
        $company = Company::current();
        // Fetched as models so the `date:Y-m-d` cast applies — `pluck()` bypasses casts.
        $holidays = Holiday::where('is_active', true)->get()->map(fn (Holiday $holiday) => $holiday->date->toDateString())->all();
        $today = today();
        $start = $today->copy()->subDays(self::ATTENDANCE_DAYS);
        $timestamp = now()->toDateTimeString();

        // Dates are pre-computed once instead of per employee, and rows are flushed in
        // batches: holding ~2,800 rows of Carbon objects in memory exhausts the heap.
        // `today()` yields a CarbonImmutable here, so the cursor must be reassigned —
        // `$day->addDay()` alone would loop forever.
        $calendar = [];
        for ($day = $start->copy(); $day->lte($today); $day = $day->addDay()) {
            $calendar[] = [
                'date' => $day->toDateString(),
                'is_weekend' => $day->isWeekend(),
                'is_holiday' => in_array($day->toDateString(), $holidays, true),
            ];
        }

        $rows = [];
        $total = 0;

        foreach ($employees as $employee) {
            $joinedAt = $employee->joined_at->toDateString();

            foreach ($calendar as $entry) {
                if ($entry['date'] < $joinedAt) {
                    continue;
                }

                $row = [
                    'employee_id' => $employee->id,
                    'date' => $entry['date'],
                    'location_id' => $employee->location_id,
                    'source' => AttendanceSource::SelfService->value,
                    'work_mode' => WorkMode::Office->value,
                    'break_minutes' => 0,
                    'overtime_minutes' => 0,
                    'worked_minutes' => 0,
                    'late_minutes' => 0,
                    'checked_in_at' => null,
                    'checked_out_at' => null,
                    'check_in_notes' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];

                $rows[] = match (true) {
                    $entry['is_weekend'] => [...$row, 'status' => AttendanceStatus::Weekend->value, 'source' => AttendanceSource::System->value],
                    $entry['is_holiday'] => [...$row, 'status' => AttendanceStatus::Holiday->value, 'source' => AttendanceSource::System->value],
                    default => [...$row, ...$this->workdayOutcome($entry['date'], $company->attendance_starts_at, $company->attendance_grace_minutes)],
                };

                if (count($rows) >= 500) {
                    DB::table('attendances')->insertOrIgnore($rows);
                    $total += count($rows);
                    $rows = [];
                }
            }
        }

        if ($rows !== []) {
            DB::table('attendances')->insertOrIgnore($rows);
            $total += count($rows);
        }

        $this->command->info('  Attendance rows: '.$total);
    }

    /**
     * @return array<string, mixed>
     */
    private function workdayOutcome(string $date, string $startsAt, int $graceMinutes): array
    {
        $roll = fake()->numberBetween(1, 100);
        $scheduledStart = Carbon::parse($date.' '.$startsAt);

        // ~4% absent, ~3% incomplete, ~18% late, the rest present.
        if ($roll <= 4) {
            return ['status' => AttendanceStatus::Absent->value, 'source' => AttendanceSource::System->value];
        }

        if ($roll <= 7) {
            $in = $scheduledStart->copy()->addMinutes(fake()->numberBetween(-10, 10));

            return [
                'status' => AttendanceStatus::Incomplete->value,
                'checked_in_at' => $in->toDateTimeString(),
                'check_in_notes' => 'Forgot to check out',
            ];
        }

        $lateMinutes = $roll <= 25 ? fake()->numberBetween($graceMinutes + 1, 75) : 0;
        $in = $scheduledStart->copy()->addMinutes($lateMinutes === 0 ? fake()->numberBetween(-25, $graceMinutes) : $lateMinutes);
        $worked = fake()->numberBetween(480, 610);
        $out = $in->copy()->addMinutes($worked);

        return [
            'status' => $lateMinutes > 0 ? AttendanceStatus::Late->value : AttendanceStatus::Present->value,
            'checked_in_at' => $in->toDateTimeString(),
            'checked_out_at' => $out->toDateTimeString(),
            'worked_minutes' => $worked,
            'break_minutes' => 60,
            'late_minutes' => $lateMinutes,
            'overtime_minutes' => max(0, $worked - 540),
            'work_mode' => fake()->randomElement([WorkMode::Office->value, WorkMode::Office->value, WorkMode::Remote->value]),
        ];
    }

    /**
     * Leave requests across all four statuses, with balances kept consistent.
     *
     * @param  Collection<int, Employee>  $employees
     */
    private function leaveRequests(Collection $employees): void
    {
        if (LeaveRequest::whereIn('employee_id', $employees->take(30)->pluck('id'))->exists()) {
            return;
        }

        $annual = LeaveType::where('code', 'ANN')->first();
        $sick = LeaveType::where('code', 'SICK')->first();

        if ($annual === null || $sick === null) {
            return;
        }

        $statuses = [
            LeaveRequestStatus::Approved,
            LeaveRequestStatus::Approved,
            LeaveRequestStatus::Pending,
            LeaveRequestStatus::Pending,
            LeaveRequestStatus::Rejected,
            LeaveRequestStatus::Cancelled,
        ];

        $sequence = 1;

        foreach ($employees->take(30) as $index => $employee) {
            $status = $statuses[$index % count($statuses)];
            $type = $index % 3 === 0 ? $sick : $annual;
            $isFuture = in_array($status, [LeaveRequestStatus::Pending, LeaveRequestStatus::Cancelled], true);

            $start = $isFuture
                ? today()->addDays(fake()->numberBetween(5, 40))
                : today()->subDays(fake()->numberBetween(5, 45));
            $days = fake()->numberBetween(1, 3);
            $end = $start->copy()->addDays($days - 1);
            $approver = $employee->manager?->user_id;

            $request = $employee->leaveRequests()->create([
                'request_number' => 'LV-'.$start->year.'-'.str_pad((string) $sequence++, 4, '0', STR_PAD_LEFT),
                'leave_type_id' => $type->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'start_session' => LeaveSession::FullDay,
                'end_session' => LeaveSession::FullDay,
                'days' => $days,
                'reason' => fake()->sentence(),
                'status' => $status,
                'submitted_by' => $employee->user_id,
                'current_approver_id' => $status === LeaveRequestStatus::Pending ? $approver : null,
                'approved_by' => $status === LeaveRequestStatus::Approved ? $approver : null,
                'approved_at' => $status === LeaveRequestStatus::Approved ? $start->copy()->subDays(2) : null,
                'rejected_by' => $status === LeaveRequestStatus::Rejected ? $approver : null,
                'rejected_at' => $status === LeaveRequestStatus::Rejected ? $start->copy()->subDays(2) : null,
                'rejection_reason' => $status === LeaveRequestStatus::Rejected ? 'Team coverage is not available for those dates.' : null,
                'cancelled_by' => $status === LeaveRequestStatus::Cancelled ? $employee->user_id : null,
                'cancelled_at' => $status === LeaveRequestStatus::Cancelled ? now()->subDay() : null,
                'cancellation_reason' => $status === LeaveRequestStatus::Cancelled ? 'Plans changed.' : null,
            ]);

            // Keep balances truthful: approved days are spent, pending days are reserved.
            if ($type->is_paid && $status->reservesBalance()) {
                $column = $status === LeaveRequestStatus::Approved ? 'used' : 'pending';
                LeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type_id', $type->id)
                    ->where('year', $start->year)
                    ->increment($column, (float) $request->days);
            }
        }
    }

    /**
     * @param  Collection<int, Employee>  $employees
     */
    private function announcements(Collection $employees): void
    {
        $author = User::where('role', UserRole::HrAdmin)->first() ?? User::firstOrFail();
        $engineering = Department::where('code', 'ENG')->first();

        $items = [
            ['title' => 'Welcome to NusaHR', 'status' => AnnouncementStatus::Published, 'pinned' => true, 'days' => -40],
            ['title' => 'Updated attendance policy', 'status' => AnnouncementStatus::Published, 'pinned' => true, 'days' => -30],
            ['title' => 'Payroll cutoff moves to the 25th', 'status' => AnnouncementStatus::Published, 'pinned' => false, 'days' => -21],
            ['title' => 'Annual health check-up schedule', 'status' => AnnouncementStatus::Published, 'pinned' => false, 'days' => -14],
            ['title' => 'Independence Day office closure', 'status' => AnnouncementStatus::Published, 'pinned' => false, 'days' => -10],
            ['title' => 'New engineering onboarding guide', 'status' => AnnouncementStatus::Published, 'pinned' => false, 'days' => -7, 'department' => true],
            ['title' => 'Quarterly all-hands recording', 'status' => AnnouncementStatus::Published, 'pinned' => false, 'days' => -3],
            ['title' => 'Office renovation next month', 'status' => AnnouncementStatus::Scheduled, 'pinned' => false, 'days' => 7],
            ['title' => 'Draft: revised leave policy', 'status' => AnnouncementStatus::Draft, 'pinned' => false, 'days' => null],
            ['title' => 'Archived: legacy VPN instructions', 'status' => AnnouncementStatus::Archived, 'pinned' => false, 'days' => -90],
        ];

        foreach ($items as $item) {
            $publishedAt = $item['days'] === null ? null : now()->addDays($item['days']);
            $targetsDepartment = ($item['department'] ?? false) && $engineering !== null;

            $announcement = Announcement::updateOrCreate(['title' => $item['title']], [
                'author_id' => $author->id,
                'slug' => Str::slug($item['title']),
                'summary' => fake()->sentence(12),
                'body' => fake()->paragraphs(3, true),
                'status' => $item['status'],
                'audience_type' => $targetsDepartment ? AnnouncementAudienceType::Departments : AnnouncementAudienceType::All,
                'audience' => 'all',
                'is_pinned' => $item['pinned'],
                'published_at' => $publishedAt,
                'notified_at' => $item['status'] === AnnouncementStatus::Published ? $publishedAt : null,
            ]);

            if ($targetsDepartment) {
                $announcement->audiences()->updateOrCreate([
                    'audienceable_type' => Department::class,
                    'audienceable_id' => $engineering->id,
                ]);
            }

            // Roughly two thirds of the workforce has opened each live announcement.
            if ($item['status'] === AnnouncementStatus::Published) {
                foreach ($employees->random(min(30, $employees->count())) as $employee) {
                    AnnouncementRead::firstOrCreate(
                        ['announcement_id' => $announcement->id, 'user_id' => $employee->user_id],
                        ['read_at' => $publishedAt?->copy()->addHours(fake()->numberBetween(1, 72))],
                    );
                }
            }
        }
    }

    /**
     * @param  Collection<int, Employee>  $employees
     */
    private function auditTrail(Collection $employees): void
    {
        $actors = User::whereIn('role', [UserRole::SuperAdmin, UserRole::HrAdmin])->pluck('id')->all();

        if ($actors === []) {
            return;
        }

        $events = [
            ['event' => 'employee.created', 'category' => 'employee', 'description' => 'Created an employee record'],
            ['event' => 'employee.updated', 'category' => 'employee', 'description' => 'Updated an employee record'],
            ['event' => 'leave.approved', 'category' => 'leave', 'description' => 'Approved a leave request'],
            ['event' => 'attendance.corrected', 'category' => 'attendance', 'description' => 'Corrected an attendance record'],
            ['event' => 'report.exported', 'category' => 'report', 'description' => 'Exported a report'],
            ['event' => 'settings.updated', 'category' => 'settings', 'description' => 'Updated company settings'],
        ];

        $rows = [];

        foreach (range(1, 60) as $i) {
            $event = $events[$i % count($events)];
            $employee = $employees->random();
            $at = now()->subDays(fake()->numberBetween(0, 45))->subMinutes(fake()->numberBetween(0, 1440));

            $rows[] = [
                'user_id' => fake()->randomElement($actors),
                'event' => $event['event'],
                'event_category' => $event['category'],
                'description' => $event['description'],
                'auditable_type' => Employee::class,
                'auditable_id' => $employee->id,
                'metadata' => json_encode(['employee_number' => $employee->employee_number]),
                'ip_address' => fake()->ipv4(),
                'user_agent' => 'Mozilla/5.0 (demo seeder)',
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }

        DB::table('audit_logs')->insert($rows);
    }
}
