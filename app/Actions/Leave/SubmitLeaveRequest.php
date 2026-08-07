<?php

namespace App\Actions\Leave;

use App\Enums\LeaveRequestStatus;
use App\Events\LeaveRequestSubmitted;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\WorkingDayCalculator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class SubmitLeaveRequest
{
    public function __construct(private WorkingDayCalculator $calculator) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Employee $employee, array $data, ?UploadedFile $attachment = null): LeaveRequest
    {
        $path = $attachment?->store("leave-attachments/{$employee->id}", 'local');
        try {
            $request = DB::transaction(function () use ($employee, $data, $path): LeaveRequest {
                $start = Carbon::parse($data['start_date']);
                $end = Carbon::parse($data['end_date']);
                $data['duration_type'] ??= 'full_day';
                $workingDays = $this->calculator->between($start, $end);
                $days = $data['duration_type'] === 'full_day' ? $workingDays : ($workingDays === 1.0 ? 0.5 : 0.0);
                if ($days <= 0) {
                    throw ValidationException::withMessages(['start_date' => 'Rentang cuti tidak memiliki hari kerja.']);
                }
                if (LeaveRequest::query()->where('employee_id', $employee->id)->whereNotIn('status', [LeaveRequestStatus::Rejected, LeaveRequestStatus::Cancelled])->where('start_date', '<=', $end)->where('end_date', '>=', $start)->exists()) {
                    throw ValidationException::withMessages(['start_date' => 'Rentang cuti bertumpang tindih.']);
                }
                $leaveType = LeaveType::query()->whereKey($data['leave_type_id'])->firstOrFail();
                if ($leaveType->is_paid) {
                    $balance = LeaveBalance::query()->where('employee_id', $employee->id)->where('leave_type_id', $data['leave_type_id'])->where('year', $start->year)->lockForUpdate()->firstOrFail();
                    if ((float) $balance->entitled - (float) $balance->used - (float) $balance->pending < $days) {
                        throw ValidationException::withMessages(['leave_type_id' => 'Saldo cuti tidak mencukupi.']);
                    }
                    $balance->increment('pending', $days);
                }

                return LeaveRequest::create([...$data, 'employee_id' => $employee->id, 'days' => $days, 'attachment_path' => $path]);
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        // Dispatched after the transaction commits: notifications and audit entries are
        // secondary, and must never be able to roll back a reserved balance.
        LeaveRequestSubmitted::dispatch($request);

        return $request;
    }
}
