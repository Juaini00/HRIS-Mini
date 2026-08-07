<?php

namespace App\Http\Controllers\Hris;

use App\Enums\AttendanceStatus;
use App\Enums\EmploymentStatus;
use App\Enums\LeaveRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\PayrollPeriod;
use App\Models\PayrollRecord;
use App\Models\PayrollRecordItem;
use App\Models\User;
use App\Support\Csv;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The reporting suite.
 *
 * Every report runs through {@see self::stream()}, which applies the same three rules
 * uniformly: the caller's permission is checked, managers are silently narrowed to their
 * own team, and each cell is sanitised against spreadsheet formula injection.
 */
class ReportController extends Controller
{
    /**
     * Report catalogue. `team` marks the reports a manager may run for their own reports.
     *
     * @return array<string, array{label: string, group: string, team: bool, filters: list<string>}>
     */
    public static function catalogue(): array
    {
        return [
            'employee-directory' => ['label' => 'Employee directory', 'group' => 'People', 'team' => true, 'filters' => []],
            'headcount-by-department' => ['label' => 'Headcount by department', 'group' => 'People', 'team' => false, 'filters' => []],
            'contract-expiration' => ['label' => 'Contract expiration', 'group' => 'People', 'team' => false, 'filters' => ['days']],
            'probation-expiration' => ['label' => 'Probation expiration', 'group' => 'People', 'team' => false, 'filters' => ['days']],
            'daily-attendance' => ['label' => 'Daily attendance', 'group' => 'Attendance', 'team' => true, 'filters' => ['date']],
            'monthly-attendance' => ['label' => 'Monthly attendance summary', 'group' => 'Attendance', 'team' => true, 'filters' => ['from', 'to']],
            'late-attendance' => ['label' => 'Late attendance', 'group' => 'Attendance', 'team' => true, 'filters' => ['from', 'to']],
            'absence' => ['label' => 'Absence', 'group' => 'Attendance', 'team' => true, 'filters' => ['from', 'to']],
            'leave-requests' => ['label' => 'Leave requests', 'group' => 'Leave', 'team' => true, 'filters' => ['from', 'to']],
            'leave-usage' => ['label' => 'Leave usage by type', 'group' => 'Leave', 'team' => false, 'filters' => ['year']],
            'leave-balances' => ['label' => 'Leave balances', 'group' => 'Leave', 'team' => true, 'filters' => ['year']],
            'payroll-summary' => ['label' => 'Payroll summary', 'group' => 'Payroll', 'team' => false, 'filters' => ['period_id']],
            'payroll-details' => ['label' => 'Payroll employee details', 'group' => 'Payroll', 'team' => false, 'filters' => ['period_id']],
            'announcement-readership' => ['label' => 'Announcement readership', 'group' => 'Communication', 'team' => false, 'filters' => []],
            'audit-activity' => ['label' => 'Audit activity', 'group' => 'Governance', 'team' => false, 'filters' => ['from', 'to']],
        ];
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->canAny([Permissions::REPORTS_VIEW_HR, Permissions::REPORTS_VIEW_TEAM]), 403);

        $isHr = $user->can(Permissions::REPORTS_VIEW_HR);

        $available = collect(self::catalogue())
            ->filter(fn (array $meta): bool => $isHr || $meta['team'])
            ->map(fn (array $meta, string $key): array => [...$meta, 'key' => $key])
            ->values()
            ->all();

        return Inertia::render('hris/reports', [
            'reports' => $available,
            'scope' => $isHr ? 'company' : 'team',
            'payrollPeriods' => $user->can(Permissions::PAYROLL_EXPORT)
                ? PayrollPeriod::query()->latest('ends_on')->get(['id', 'name'])
                : [],
        ]);
    }

    /**
     * Single entry point for every CSV export.
     */
    public function export(Request $request, string $report): StreamedResponse
    {
        $catalogue = self::catalogue();
        abort_unless(isset($catalogue[$report]), 404);

        $user = $request->user();
        $meta = $catalogue[$report];
        $isHr = $user->can(Permissions::REPORTS_VIEW_HR);

        // Managers only reach the team-safe subset, and payroll needs its own permission.
        abort_unless($isHr || ($meta['team'] && $user->can(Permissions::REPORTS_VIEW_TEAM)), 403);

        if ($meta['group'] === 'Payroll') {
            abort_unless($user->can(Permissions::PAYROLL_EXPORT), 403);
        }

        $filters = $this->validateFilters($request, $meta['filters']);
        $teamIds = $isHr ? null : ($user->employee?->descendantIds() ?? []);

        $this->audit($request, $report, $filters);

        return match ($report) {
            'employee-directory' => $this->employeeDirectory($teamIds),
            'headcount-by-department' => $this->headcountByDepartment(),
            'contract-expiration' => $this->expiringDates('contract_ends_on', 'contract-expiration', $filters),
            'probation-expiration' => $this->expiringDates('probation_ends_on', 'probation-expiration', $filters),
            'daily-attendance' => $this->dailyAttendance($filters, $teamIds),
            'monthly-attendance' => $this->monthlyAttendance($filters, $teamIds),
            'late-attendance' => $this->attendanceByStatus(AttendanceStatus::Late, 'late-attendance', $filters, $teamIds),
            'absence' => $this->attendanceByStatus(AttendanceStatus::Absent, 'absence', $filters, $teamIds),
            'leave-requests' => $this->leaveRequests($filters, $teamIds),
            'leave-usage' => $this->leaveUsage($filters),
            'leave-balances' => $this->leaveBalances($filters, $teamIds),
            'payroll-summary' => $this->payrollSummary($filters),
            'payroll-details' => $this->payrollDetails($filters),
            'announcement-readership' => $this->announcementReadership(),
            default => $this->auditActivity($filters),
        };
    }

    /**
     * @param  list<string>  $expected
     * @return array<string, mixed>
     */
    private function validateFilters(Request $request, array $expected): array
    {
        $rules = [];

        foreach ($expected as $filter) {
            $rules[$filter] = match ($filter) {
                'from' => ['required', 'date'],
                'to' => ['required', 'date', 'after_or_equal:from'],
                'date' => ['required', 'date'],
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
                'days' => ['nullable', 'integer', 'min:1', 'max:365'],
                'period_id' => ['required', 'exists:payroll_periods,id'],
                default => ['nullable'],
            };
        }

        return $request->validate($rules);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function audit(Request $request, string $report, array $filters): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'event' => 'report.exported',
            'event_category' => 'report',
            'description' => "Exported the {$report} report",
            'metadata' => ['report' => $report, 'filters' => $filters],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * @param  list<int>|null  $teamIds
     * @return Builder<Employee>
     */
    private function employeeScope(?array $teamIds): Builder
    {
        return Employee::query()->when($teamIds !== null, fn (Builder $q) => $q->whereIn('id', $teamIds));
    }

    /**
     * @param  list<int>|null  $teamIds
     */
    private function employeeDirectory(?array $teamIds): StreamedResponse
    {
        return $this->stream(
            'employee-directory.csv',
            ['Employee Number', 'Name', 'Work Email', 'Department', 'Position', 'Location', 'Employment Type', 'Status', 'Manager', 'Joined'],
            $this->employeeScope($teamIds)->with(['user', 'department', 'position', 'location', 'employmentType', 'manager.user'])->orderBy('employee_number'),
            fn (Employee $e): array => [
                $e->employee_number,
                $e->user->name,
                $e->work_email,
                $e->department?->name,
                $e->position?->name,
                $e->location?->name,
                $e->employmentType?->name,
                $e->employment_status->label(),
                $e->manager?->user->name,
                $e->joined_at->toDateString(),
            ],
        );
    }

    private function headcountByDepartment(): StreamedResponse
    {
        return $this->stream(
            'headcount-by-department.csv',
            ['Department', 'Code', 'Active Headcount'],
            Department::query()
                ->withCount(['employees as active_headcount' => fn (Builder $q) => $q->whereIn('employment_status', EmploymentStatus::employedValues())])
                ->orderBy('name'),
            fn (Department $d): array => [$d->name, $d->code, $d->getAttribute('active_headcount')],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function expiringDates(string $column, string $name, array $filters): StreamedResponse
    {
        $days = (int) ($filters['days'] ?? 90);

        return $this->stream(
            "{$name}.csv",
            ['Employee Number', 'Name', 'Department', 'Employment Type', 'Expires On', 'Days Remaining'],
            Employee::query()
                ->whereNotNull($column)
                ->whereBetween($column, [today()->toDateString(), today()->addDays($days)->toDateString()])
                ->with(['user', 'department', 'employmentType'])
                ->orderBy($column),
            fn (Employee $e) => [
                $e->employee_number,
                $e->user->name,
                $e->department?->name,
                $e->employmentType?->name,
                $e->{$column}?->toDateString(),
                $e->{$column} ? (int) round(today()->diffInDays($e->{$column}, false)) : null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<int>|null  $teamIds
     */
    private function dailyAttendance(array $filters, ?array $teamIds): StreamedResponse
    {
        return $this->stream(
            'daily-attendance.csv',
            ['Date', 'Employee Number', 'Name', 'Department', 'Status', 'Check In', 'Check Out', 'Worked Minutes', 'Late Minutes', 'Work Mode'],
            $this->attendanceQuery($teamIds)->where('date', $filters['date'])->orderBy('employee_id'),
            fn (Attendance $a): array => [
                $a->date->toDateString(),
                $a->employee->employee_number,
                $a->employee->user->name,
                $a->employee->department?->name,
                $a->status->label(),
                $a->checked_in_at?->toDateTimeString(),
                $a->checked_out_at?->toDateTimeString(),
                $a->worked_minutes,
                $a->late_minutes,
                $a->work_mode->label(),
            ],
        );
    }

    /**
     * Per-employee attendance totals for a date range.
     *
     * @param  array<string, mixed>  $filters
     * @param  list<int>|null  $teamIds
     */
    private function monthlyAttendance(array $filters, ?array $teamIds): StreamedResponse
    {
        $statusColumns = [
            AttendanceStatus::Present,
            AttendanceStatus::Late,
            AttendanceStatus::Absent,
            AttendanceStatus::OnLeave,
            AttendanceStatus::Incomplete,
        ];

        $query = $this->employeeScope($teamIds)->with(['user', 'department']);

        foreach ($statusColumns as $status) {
            $query->withCount(['attendances as '.$status->value.'_days' => fn (Builder $q) => $q
                ->whereBetween('date', [$filters['from'], $filters['to']])
                ->where('status', $status->value)]);
        }

        return $this->stream(
            'monthly-attendance.csv',
            ['Employee Number', 'Name', 'Department', 'Present', 'Late', 'Absent', 'On Leave', 'Incomplete'],
            $query->orderBy('employee_number'),
            fn (Employee $e): array => [
                $e->employee_number,
                $e->user->name,
                $e->department?->name,
                $e->getAttribute('present_days'),
                $e->getAttribute('late_days'),
                $e->getAttribute('absent_days'),
                $e->getAttribute('leave_days'),
                $e->getAttribute('incomplete_days'),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<int>|null  $teamIds
     */
    private function attendanceByStatus(AttendanceStatus $status, string $name, array $filters, ?array $teamIds): StreamedResponse
    {
        return $this->stream(
            "{$name}.csv",
            ['Date', 'Employee Number', 'Name', 'Department', 'Late Minutes', 'Check In'],
            $this->attendanceQuery($teamIds)
                ->where('status', $status->value)
                ->whereBetween('date', [$filters['from'], $filters['to']])
                ->orderBy('date'),
            fn (Attendance $a): array => [
                $a->date->toDateString(),
                $a->employee->employee_number,
                $a->employee->user->name,
                $a->employee->department?->name,
                $a->late_minutes,
                $a->checked_in_at?->toDateTimeString(),
            ],
        );
    }

    /**
     * @param  list<int>|null  $teamIds
     * @return Builder<Attendance>
     */
    private function attendanceQuery(?array $teamIds): Builder
    {
        return Attendance::query()
            ->with(['employee.user', 'employee.department'])
            ->when($teamIds !== null, fn (Builder $q) => $q->whereIn('employee_id', $teamIds));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<int>|null  $teamIds
     */
    private function leaveRequests(array $filters, ?array $teamIds): StreamedResponse
    {
        return $this->stream(
            'leave-requests.csv',
            ['Request Number', 'Employee Number', 'Name', 'Leave Type', 'Start', 'End', 'Days', 'Status', 'Approved By'],
            LeaveRequest::query()
                ->with(['employee.user', 'leaveType'])
                ->when($teamIds !== null, fn (Builder $q) => $q->whereIn('employee_id', $teamIds))
                ->where('start_date', '<=', $filters['to'])
                ->where('end_date', '>=', $filters['from'])
                ->orderBy('start_date'),
            fn (LeaveRequest $r): array => [
                $r->request_number,
                $r->employee->employee_number,
                $r->employee->user->name,
                $r->leaveType->name,
                $r->start_date->toDateString(),
                $r->end_date->toDateString(),
                $r->days,
                $r->status->label(),
                $r->approved_by,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function leaveUsage(array $filters): StreamedResponse
    {
        return $this->stream(
            'leave-usage.csv',
            ['Leave Type', 'Code', 'Approved Requests', 'Total Days'],
            LeaveType::query()
                ->withCount(['requests as approved_requests' => fn (Builder $q) => $q
                    ->where('status', LeaveRequestStatus::Approved->value)
                    ->whereYear('start_date', $filters['year'])])
                ->withSum(['requests as total_days' => fn (Builder $q) => $q
                    ->where('status', LeaveRequestStatus::Approved->value)
                    ->whereYear('start_date', $filters['year'])], 'days')
                ->orderBy('name'),
            fn (LeaveType $t): array => [$t->name, $t->code, $t->getAttribute('approved_requests'), $t->getAttribute('total_days') ?? 0],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<int>|null  $teamIds
     */
    private function leaveBalances(array $filters, ?array $teamIds): StreamedResponse
    {
        return $this->stream(
            'leave-balances.csv',
            ['Employee Number', 'Name', 'Leave Type', 'Year', 'Entitled', 'Carried Forward', 'Used', 'Pending', 'Adjustment', 'Remaining'],
            LeaveBalance::query()
                ->with(['employee.user', 'leaveType'])
                ->where('year', $filters['year'])
                ->when($teamIds !== null, fn (Builder $q) => $q->whereIn('employee_id', $teamIds))
                ->orderBy('employee_id'),
            fn (LeaveBalance $b): array => [
                $b->employee->employee_number,
                $b->employee->user->name,
                $b->leaveType->name,
                $b->year,
                $b->entitled,
                $b->carried_forward,
                $b->used,
                $b->pending,
                $b->adjustment,
                $b->remaining,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function payrollSummary(array $filters): StreamedResponse
    {
        return $this->stream(
            'payroll-summary.csv',
            ['Employee Number', 'Name', 'Department', 'Basic Salary', 'Earnings', 'Deductions', 'Net Salary', 'Working Days', 'Absent Days'],
            PayrollRecord::query()
                ->with(['employee.user', 'employee.department'])
                ->where('payroll_period_id', $filters['period_id'])
                ->orderBy('employee_id'),
            fn (PayrollRecord $r): array => [
                $r->employee->employee_number,
                $r->employee->user->name,
                $r->employee->department?->name,
                $r->basic_salary,
                $r->earnings,
                $r->deductions,
                $r->net_salary,
                $r->working_days,
                $r->absent_days,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function payrollDetails(array $filters): StreamedResponse
    {
        return $this->stream(
            'payroll-details.csv',
            ['Employee Number', 'Name', 'Component', 'Type', 'Amount', 'Manual', 'Notes'],
            PayrollRecordItem::query()
                ->with(['record.employee.user'])
                ->whereHas('record', fn (Builder $q) => $q->where('payroll_period_id', $filters['period_id']))
                ->orderBy('payroll_record_id'),
            fn (PayrollRecordItem $i): array => [
                $i->record->employee->employee_number,
                $i->record->employee->user->name,
                $i->name,
                $i->type->label(),
                $i->amount,
                $i->is_manual ? 'yes' : 'no',
                $i->notes,
            ],
        );
    }

    private function announcementReadership(): StreamedResponse
    {
        return $this->stream(
            'announcement-readership.csv',
            ['Title', 'Status', 'Published At', 'Pinned', 'Read Count'],
            Announcement::query()->withCount('reads')->orderByDesc('published_at'),
            fn (Announcement $a): array => [
                $a->title,
                $a->status->label(),
                $a->published_at?->toDateTimeString(),
                $a->is_pinned ? 'yes' : 'no',
                $a->getAttribute('reads_count'),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function auditActivity(array $filters): StreamedResponse
    {
        return $this->stream(
            'audit-activity.csv',
            ['Timestamp', 'Actor', 'Event', 'Category', 'Description', 'Subject', 'IP Address'],
            AuditLog::query()
                ->with('user')
                ->whereBetween('created_at', [$filters['from'].' 00:00:00', $filters['to'].' 23:59:59'])
                ->orderByDesc('created_at'),
            fn (AuditLog $log): array => [
                $log->created_at?->toDateTimeString(),
                // System-generated entries have no actor.
                $log->getRelationValue('user') instanceof User ? $log->getRelationValue('user')->name : 'system',
                $log->event,
                $log->event_category,
                $log->description,
                $log->auditable_type ? class_basename($log->auditable_type).'#'.$log->auditable_id : null,
                $log->ip_address,
            ],
        );
    }

    /**
     * Stream a report as CSV.
     *
     * Rows are read lazily so a large export never loads the whole result set into memory,
     * and every cell goes through {@see Csv::safe()} to defuse spreadsheet formula injection.
     *
     * @template TModel of Model
     *
     * @param  list<string>  $header
     * @param  Builder<TModel>  $query
     * @param  callable(TModel): array<int, mixed>  $map
     */
    private function stream(string $filename, array $header, Builder $query, callable $map): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $query, $map): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                return;
            }

            fputcsv($out, $header);

            foreach ($query->lazy(500) as $row) {
                fputcsv($out, Csv::safeRow($map($row)));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
