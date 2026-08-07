<?php

namespace App\Http\Controllers\Hris;

use App\Actions\Attendance\CorrectAttendance;
use App\Actions\Attendance\RecordAttendance;
use App\Actions\Attendance\ReviewAttendanceCorrection;
use App\Actions\Attendance\SubmitAttendanceCorrection;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\CorrectAttendanceRequest;
use App\Http\Requests\Attendance\StoreAttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Support\Permissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request, RecordAttendance $record): Response
    {
        $employee = $request->user()->employee;
        $employeeId = $employee?->id;
        $query = Attendance::query()->with('employee.user')->latest('date');
        if (! $request->user()->isAdministrator()) {
            $request->user()->role === UserRole::Manager
                ? $query->where(fn ($attendance) => $attendance->where('employee_id', $employeeId ?? 0)->orWhereHas('employee', fn ($reports) => $reports->where('manager_id', $employeeId)))
                : $query->where('employee_id', $employeeId ?? 0);
        }

        $canCorrect = $request->user()->can(Permissions::ATTENDANCE_CORRECT);

        return Inertia::render('hris/attendance', [
            'attendances' => $query->with('corrections')->paginate(25)->withQueryString(),
            'today' => $employee?->attendances()->where('date', today()->toDateString())->first(),
            'checkInBlockedReason' => $employee ? $record->checkInBlockedReason($employee) : null,
            'canCorrect' => $canCorrect,
            'corrections' => $this->corrections($request, $employeeId, $canCorrect),
        ]);
    }

    /**
     * Correction requests the viewer is entitled to see: HR sees the pending queue,
     * everyone else sees only their own.
     *
     * @return array<int, mixed>
     */
    private function corrections(Request $request, ?int $employeeId, bool $canCorrect): array
    {
        return AttendanceCorrection::query()
            ->with(['attendance:id,employee_id,date,status', 'attendance.employee:id,user_id,employee_number', 'attendance.employee.user:id,name', 'requester:id,name'])
            ->when(
                $canCorrect,
                fn (Builder $query) => $query->orderByRaw("case when status = 'pending' then 0 else 1 end"),
                fn (Builder $query) => $query->where('requested_by', $request->user()->id),
            )
            ->latest()
            ->limit(20)
            ->get()
            ->all();
    }

    /**
     * An employee reports a problem with their own attendance record.
     */
    public function requestCorrection(
        StoreAttendanceCorrectionRequest $request,
        Attendance $attendance,
        SubmitAttendanceCorrection $submit,
    ): RedirectResponse {
        $submit->handle($attendance, $request->user(), $request->validated());

        return back()->with('success', 'Permintaan koreksi dikirim ke HR.');
    }

    /**
     * HR approves or rejects a correction request.
     */
    public function reviewCorrection(
        Request $request,
        AttendanceCorrection $attendanceCorrection,
        ReviewAttendanceCorrection $review,
    ): RedirectResponse {
        Gate::authorize('review', $attendanceCorrection);

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $review->handle(
            $attendanceCorrection,
            $request->user(),
            $data['decision'] === 'approve',
            $data['review_notes'] ?? null,
        );

        return back()->with('success', 'Permintaan koreksi diproses.');
    }

    public function checkIn(Request $request, RecordAttendance $record): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee !== null, 403);
        $record->checkIn($employee);

        return back()->with('success', 'Check-in berhasil.');
    }

    public function checkOut(Request $request, RecordAttendance $record): RedirectResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee !== null, 403);
        $record->checkOut($employee);

        return back()->with('success', 'Check-out berhasil.');
    }

    public function correct(CorrectAttendanceRequest $request, Attendance $attendance, CorrectAttendance $correct): RedirectResponse
    {
        // The action raises AttendanceCorrected, which carries the before/after values to
        // the audit listener — the controller no longer has to remember to log it.
        $correct->handle($attendance, [...$request->validated(), 'updated_by' => $request->user()->id], $request->user());

        return back()->with('success', 'Kehadiran berhasil dikoreksi.');
    }
}
