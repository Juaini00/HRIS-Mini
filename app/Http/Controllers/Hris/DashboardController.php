<?php

namespace App\Http\Controllers\Hris;

use App\Enums\PayrollStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $employee = $request->user()->employee;
        $announcements = Announcement::query()->whereNotNull('published_at')->where('published_at', '<=', now())->whereIn('audience', ['all', $request->user()->role->value])
            ->where(fn ($query) => $query->whereNull('department_id')->orWhere('department_id', $employee?->department_id))
            ->where(fn ($query) => $query->whereNull('location_id')->orWhere('location_id', $employee?->location_id))
            ->latest('published_at')->limit(5)->get();

        return Inertia::render('dashboard', [
            'stats' => ['employees' => Employee::whereNull('ended_at')->count(), 'departments' => Department::where('is_active', true)->count(), 'pendingLeave' => $request->user()->isAdministrator() ? LeaveRequest::where('status', 'pending')->count() : $employee?->leaveRequests()->where('status', 'pending')->count() ?? 0, 'presentToday' => Attendance::where('date', today())->where('status', 'present')->count()],
            'myAttendance' => $employee?->attendances()->where('date', today())->first(),
            'myLeave' => $employee?->leaveRequests()->latest()->limit(5)->with('leaveType')->get() ?? [],
            'announcements' => $announcements,
            'payrollPeriods' => PayrollPeriod::where('status', PayrollStatus::Published)->latest('ends_on')->limit(3)->get(),
        ]);
    }
}
