<?php

namespace App\Http\Controllers\Hris;

use App\Enums\AttendanceStatus;
use App\Enums\EmploymentStatus;
use App\Enums\LeaveRequestStatus;
use App\Enums\PayrollPeriodStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Role-aware dashboard.
 *
 * Every figure is an aggregate query against live data — nothing here is hard-coded.
 * Counts are grouped in as few round trips as practical rather than one query per tile.
 */
class DashboardController extends Controller
{
    private const ATTENDANCE_TREND_DAYS = 30;

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $employee = $user->employee;

        $payload = match (true) {
            $user->isAdministrator() => $this->administratorDashboard($user),
            $user->role === UserRole::Manager => $this->managerDashboard($employee),
            default => [],
        };

        return Inertia::render('dashboard', [
            'role' => $user->role->value,
            'canSeePayrollValue' => $user->can(Permissions::PAYROLL_VIEW_ALL),
            'personal' => $this->personalPanel($employee),
            'announcements' => $this->recentAnnouncements(),
            ...$payload,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function administratorDashboard(User $user): array
    {
        $today = today()->toDateString();

        return [
            'stats' => [
                ...$this->headcountStats(),
                ...$this->attendanceStats($today),
                'pendingLeave' => LeaveRequest::where('status', LeaveRequestStatus::Pending)->count(),
                'payroll' => $this->currentPayrollStatus($user),
            ],
            'charts' => [
                'byDepartment' => $this->headcountBy('departments', 'department_id'),
                'byEmploymentType' => $this->headcountBy('employment_types', 'employment_type_id'),
                'attendanceTrend' => $this->attendanceTrend(),
                'leaveUsageByType' => $this->leaveUsageByType(),
            ],
            'lists' => [
                'pendingApprovals' => $this->pendingApprovals(),
                'recentHires' => $this->recentHires(),
                'birthdays' => $this->birthdaysThisMonth(),
                'contractExpirations' => $this->contractExpirations(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function managerDashboard(?Employee $manager): array
    {
        if ($manager === null) {
            return ['stats' => [], 'lists' => []];
        }

        $teamIds = $manager->descendantIds();
        $today = today()->toDateString();

        if ($teamIds === []) {
            return ['stats' => ['directReports' => 0], 'lists' => ['pendingApprovals' => [], 'teamOnLeave' => []]];
        }

        $attendance = Attendance::whereIn('employee_id', $teamIds)
            ->where('date', $today)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'stats' => [
                'directReports' => $manager->reports()->count(),
                'teamSize' => count($teamIds),
                'presentToday' => (int) ($attendance[AttendanceStatus::Present->value] ?? 0) + (int) ($attendance[AttendanceStatus::Late->value] ?? 0),
                'absentToday' => (int) ($attendance[AttendanceStatus::Absent->value] ?? 0),
                'onLeaveToday' => (int) ($attendance[AttendanceStatus::OnLeave->value] ?? 0),
                'pendingLeave' => LeaveRequest::whereIn('employee_id', $teamIds)->where('status', LeaveRequestStatus::Pending)->count(),
            ],
            'lists' => [
                'pendingApprovals' => $this->pendingApprovals($teamIds),
                'teamOnLeave' => $this->upcomingTeamLeave($teamIds),
            ],
        ];
    }

    /**
     * The employee-facing panel, shown to every role so managers and HR also see their own data.
     *
     * @return array<string, mixed>|null
     */
    private function personalPanel(?Employee $employee): ?array
    {
        if ($employee === null) {
            return null;
        }

        $monthStart = today()->startOfMonth()->toDateString();
        $monthlySummary = Attendance::where('employee_id', $employee->id)
            ->where('date', '>=', $monthStart)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $latestPayslip = $employee->payrollRecords()
            ->whereHas('period', fn (Builder $query) => $query->whereIn('status', [PayrollPeriodStatus::Published, PayrollPeriodStatus::Closed]))
            ->with('period:id,name,payment_date')
            ->latest('id')
            ->first(['id', 'payroll_period_id', 'net_salary']);

        return [
            'employeeNumber' => $employee->employee_number,
            'today' => Attendance::where('employee_id', $employee->id)->where('date', today()->toDateString())->first(),
            'monthlySummary' => $monthlySummary,
            'leaveBalances' => $employee->leaveBalances()
                ->where('year', now()->year)
                ->with('leaveType:id,name,color')
                ->get(),
            'pendingLeave' => $employee->leaveRequests()->where('status', LeaveRequestStatus::Pending)->count(),
            'upcomingLeave' => $employee->leaveRequests()
                ->where('status', LeaveRequestStatus::Approved)
                ->where('start_date', '>=', today()->toDateString())
                ->with('leaveType:id,name,color')
                ->orderBy('start_date')
                ->limit(3)
                ->get(),
            'latestPayslip' => $latestPayslip,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function headcountStats(): array
    {
        $byStatus = Employee::selectRaw('employment_status, count(*) as total')
            ->groupBy('employment_status')
            ->pluck('total', 'employment_status');

        $employed = collect(EmploymentStatus::employedValues())
            ->sum(fn (string $status): int => (int) ($byStatus[$status] ?? 0));

        return [
            'activeEmployees' => $employed,
            'totalEmployees' => (int) $byStatus->sum(),
            'onProbation' => (int) ($byStatus[EmploymentStatus::Probation->value] ?? 0),
            'newHiresThisMonth' => Employee::where('joined_at', '>=', today()->startOfMonth()->toDateString())->count(),
            'contractsEndingSoon' => Employee::whereNotNull('contract_ends_on')
                ->whereBetween('contract_ends_on', [today()->toDateString(), today()->addDays(60)->toDateString()])
                ->count(),
            'probationEndingSoon' => Employee::whereNotNull('probation_ends_on')
                ->whereBetween('probation_ends_on', [today()->toDateString(), today()->addDays(30)->toDateString()])
                ->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function attendanceStats(string $today): array
    {
        $counts = Attendance::where('date', $today)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'presentToday' => (int) ($counts[AttendanceStatus::Present->value] ?? 0),
            'lateToday' => (int) ($counts[AttendanceStatus::Late->value] ?? 0),
            'absentToday' => (int) ($counts[AttendanceStatus::Absent->value] ?? 0),
            'onLeaveToday' => (int) ($counts[AttendanceStatus::OnLeave->value] ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function currentPayrollStatus(User $user): array
    {
        $period = PayrollPeriod::query()
            ->whereYear('starts_on', now()->year)
            ->whereMonth('starts_on', now()->month)
            ->withCount('records')
            ->first();

        if ($period === null) {
            return ['status' => null, 'records' => 0, 'totalNet' => null];
        }

        return [
            'name' => $period->name,
            'status' => $period->status->value,
            'records' => $period->records_count,
            // Payroll value is restricted: only users with payroll.view-all get the figure.
            'totalNet' => $user->can(Permissions::PAYROLL_VIEW_ALL)
                ? (float) $period->records()->sum('net_salary')
                : null,
        ];
    }

    /**
     * Headcount grouped by a related lookup table, in a single joined query.
     *
     * @return list<array{label: string, value: int}>
     */
    private function headcountBy(string $table, string $foreignKey): array
    {
        // Query builder rather than Eloquent: this is a pure aggregate, so hydrating
        // Employee models would be wasted work and the columns aren't real attributes.
        $rows = DB::table('employees')
            ->join($table, "{$table}.id", '=', "employees.{$foreignKey}")
            ->whereIn('employees.employment_status', EmploymentStatus::employedValues())
            ->groupBy("{$table}.name")
            ->orderByDesc('value')
            // `select()` quotes the identifier for us; only the aggregate needs to be raw,
            // and that stays a literal so no variable ever reaches raw SQL.
            ->select(["{$table}.name as label"])
            ->selectRaw('count(*) as value')
            ->get();

        return array_values($rows->map(fn (object $row): array => [
            'label' => (string) $row->label,
            'value' => (int) $row->value,
        ])->all());
    }

    /**
     * Daily attendance counts for the trend chart, as one grouped query.
     *
     * @return list<array{date: string, present: int, late: int, absent: int, leave: int}>
     */
    private function attendanceTrend(): array
    {
        $from = today()->subDays(self::ATTENDANCE_TREND_DAYS)->toDateString();

        $rows = DB::table('attendances')
            ->where('date', '>=', $from)
            ->whereIn('status', [
                AttendanceStatus::Present->value,
                AttendanceStatus::Late->value,
                AttendanceStatus::Absent->value,
                AttendanceStatus::OnLeave->value,
            ])
            ->groupBy('date', 'status')
            ->get(['date', 'status', DB::raw('count(*) as total')]);

        $series = [];

        foreach ($rows as $row) {
            $date = substr((string) $row->date, 0, 10);
            $series[$date] ??= ['date' => $date, 'present' => 0, 'late' => 0, 'absent' => 0, 'leave' => 0];

            $key = match ((string) $row->status) {
                AttendanceStatus::Present->value => 'present',
                AttendanceStatus::Late->value => 'late',
                AttendanceStatus::Absent->value => 'absent',
                default => 'leave',
            };

            $series[$date][$key] = (int) $row->total;
        }

        ksort($series);

        return array_values($series);
    }

    /**
     * @return list<array{label: string, value: float, color: string}>
     */
    private function leaveUsageByType(): array
    {
        $rows = DB::table('leave_requests')
            ->join('leave_types', 'leave_types.id', '=', 'leave_requests.leave_type_id')
            ->where('leave_requests.status', LeaveRequestStatus::Approved->value)
            ->whereYear('leave_requests.start_date', now()->year)
            ->groupBy('leave_types.name', 'leave_types.color')
            ->orderByDesc('value')
            ->get([
                DB::raw('leave_types.name as label'),
                DB::raw('leave_types.color as color'),
                DB::raw('sum(leave_requests.days) as value'),
            ]);

        return array_values($rows->map(fn (object $row): array => [
            'label' => (string) $row->label,
            'value' => (float) $row->value,
            'color' => (string) $row->color,
        ])->all());
    }

    /**
     * @param  list<int>|null  $employeeIds
     * @return array<int, mixed>
     */
    private function pendingApprovals(?array $employeeIds = null): array
    {
        return LeaveRequest::query()
            ->where('status', LeaveRequestStatus::Pending)
            ->when($employeeIds !== null, fn (Builder $query) => $query->whereIn('employee_id', $employeeIds))
            ->with(['employee:id,user_id,employee_number,first_name,last_name', 'employee.user:id,name', 'leaveType:id,name,color'])
            ->orderBy('start_date')
            ->limit(8)
            ->get()
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    private function recentHires(): array
    {
        return Employee::query()
            ->with(['user:id,name', 'position:id,name', 'department:id,name'])
            ->orderByDesc('joined_at')
            ->limit(5)
            ->get(['id', 'user_id', 'position_id', 'department_id', 'employee_number', 'joined_at'])
            ->all();
    }

    /**
     * Birthdays falling in the current calendar month.
     *
     * @return array<int, mixed>
     */
    private function birthdaysThisMonth(): array
    {
        return Employee::query()
            ->currentlyEmployed()
            ->whereNotNull('date_of_birth')
            ->whereRaw(DB::getDriverName() === 'pgsql'
                ? 'extract(month from date_of_birth) = ?'
                : "cast(strftime('%m', date_of_birth) as integer) = ?", [now()->month])
            ->with('user:id,name')
            ->orderByRaw(DB::getDriverName() === 'pgsql'
                ? 'extract(day from date_of_birth)'
                : "cast(strftime('%d', date_of_birth) as integer)")
            ->limit(10)
            ->get(['id', 'user_id', 'employee_number', 'date_of_birth'])
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    private function contractExpirations(): array
    {
        return Employee::query()
            ->currentlyEmployed()
            ->whereNotNull('contract_ends_on')
            ->whereBetween('contract_ends_on', [today()->toDateString(), today()->addDays(90)->toDateString()])
            ->with(['user:id,name', 'employmentType:id,name'])
            ->orderBy('contract_ends_on')
            ->limit(10)
            ->get(['id', 'user_id', 'employment_type_id', 'employee_number', 'contract_ends_on'])
            ->all();
    }

    /**
     * @param  list<int>  $teamIds
     * @return array<int, mixed>
     */
    private function upcomingTeamLeave(array $teamIds): array
    {
        return LeaveRequest::query()
            ->whereIn('employee_id', $teamIds)
            ->where('status', LeaveRequestStatus::Approved)
            ->where('end_date', '>=', today()->toDateString())
            ->with(['employee.user:id,name', 'leaveType:id,name,color'])
            ->orderBy('start_date')
            ->limit(8)
            ->get()
            ->all();
    }

    /**
     * @return array<int, mixed>
     */
    private function recentAnnouncements(): array
    {
        return Announcement::query()
            ->visible()
            ->with('author:id,name')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->limit(5)
            ->get(['id', 'author_id', 'title', 'slug', 'summary', 'is_pinned', 'published_at'])
            ->all();
    }
}
