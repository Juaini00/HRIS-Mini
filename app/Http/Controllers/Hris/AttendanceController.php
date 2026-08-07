<?php

namespace App\Http\Controllers\Hris;

use App\Actions\Attendance\CorrectAttendance;
use App\Actions\Attendance\RecordAttendance;
use App\Actions\Audit\WriteAuditLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\CorrectAttendanceRequest;
use App\Models\Attendance;
use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $employee = $request->user()->employee;
        $query = Attendance::query()->with('employee.user')->latest('date');
        if (! $request->user()->isAdministrator()) {
            $request->user()->role === UserRole::Manager
                ? $query->where(fn ($attendance) => $attendance->where('employee_id', $employee?->id ?? 0)->orWhereHas('employee', fn ($reports) => $reports->where('manager_id', $employee?->id)))
                : $query->where('employee_id', $employee?->id ?? 0);
        }

        return Inertia::render('hris/attendance', ['attendances' => $query->paginate(25)->withQueryString(), 'today' => $employee?->attendances()->where('date', today())->first(), 'canCorrect' => $request->user()->isAdministrator()]);
    }

    public function checkIn(Request $request, RecordAttendance $record): RedirectResponse
    {
        abort_unless($request->user()->employee, 403); $record->checkIn($request->user()->employee);
        return back()->with('success', 'Check-in berhasil.');
    }

    public function checkOut(Request $request, RecordAttendance $record): RedirectResponse
    {
        abort_unless($request->user()->employee, 403); $record->checkOut($request->user()->employee);
        return back()->with('success', 'Check-out berhasil.');
    }

    public function correct(CorrectAttendanceRequest $request, Attendance $attendance, CorrectAttendance $correct, WriteAuditLog $audit): RedirectResponse
    {
        $before = $attendance->only(['checked_in_at', 'checked_out_at', 'worked_minutes', 'status']);
        $correct->handle($attendance, $request->validated());
        $audit->handle($request, 'attendance.corrected', $attendance, ['before' => $before, 'after' => $attendance->fresh()->only(array_keys($before)), 'reason' => $request->input('correction_reason')]);

        return back()->with('success', 'Kehadiran berhasil dikoreksi.');
    }
}
