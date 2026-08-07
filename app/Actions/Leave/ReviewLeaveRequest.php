<?php

namespace App\Actions\Leave;

use App\Enums\LeaveRequestStatus;
use App\Events\LeaveRequestReviewed;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewLeaveRequest
{
    public function handle(LeaveRequest $request, User $reviewer, LeaveRequestStatus $status, ?string $notes = null): LeaveRequest
    {
        $request = DB::transaction(function () use ($request, $reviewer, $status, $notes): LeaveRequest {
            $request = LeaveRequest::query()->lockForUpdate()->findOrFail($request->id);
            if ($request->status !== LeaveRequestStatus::Pending || ! in_array($status, [LeaveRequestStatus::Approved, LeaveRequestStatus::Rejected], true)) {
                throw ValidationException::withMessages(['status' => 'Permintaan tidak dapat diproses lagi.']);
            }
            if ($request->leaveType->is_paid) {
                $balance = LeaveBalance::query()->where('employee_id', $request->employee_id)->where('leave_type_id', $request->leave_type_id)->where('year', $request->start_date->year)->lockForUpdate()->firstOrFail();
                $balance->decrement('pending', (float) $request->days);
                if ($status === LeaveRequestStatus::Approved) {
                    $balance->increment('used', (float) $request->days);
                }
            }
            $request->update(['status' => $status, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'review_notes' => $notes]);

            return $request->refresh();
        });
        LeaveRequestReviewed::dispatch($request, $reviewer, $status);

        return $request;
    }
}
