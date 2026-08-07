<?php
namespace App\Actions\Leave;
use App\Enums\LeaveStatus;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ReviewLeaveRequest { public function handle(LeaveRequest $request, User $reviewer, LeaveStatus $status, ?string $notes=null): LeaveRequest { return DB::transaction(function () use ($request,$reviewer,$status,$notes) { $request=LeaveRequest::lockForUpdate()->findOrFail($request->id); if ($request->status!==LeaveStatus::Pending || !in_array($status,[LeaveStatus::Approved,LeaveStatus::Rejected],true)) { throw ValidationException::withMessages(['status'=>'Permintaan tidak dapat diproses lagi.']); } $balance=LeaveBalance::where('employee_id',$request->employee_id)->where('leave_type_id',$request->leave_type_id)->where('year',$request->start_date->year)->lockForUpdate()->firstOrFail(); $balance->decrement('pending',$request->days); if ($status===LeaveStatus::Approved) { $balance->increment('used',$request->days); } $request->update(['status'=>$status,'reviewed_by'=>$reviewer->id,'reviewed_at'=>now(),'review_notes'=>$notes]); return $request->refresh(); }); } }
