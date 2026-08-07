<?php
namespace App\Actions\Leave;
use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Services\WorkingDayCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class SubmitLeaveRequest { public function __construct(private WorkingDayCalculator $calculator) {} public function handle(Employee $employee, array $data): LeaveRequest { return DB::transaction(function () use ($employee,$data) { $start=Carbon::parse($data['start_date']); $end=Carbon::parse($data['end_date']); $days=$this->calculator->between($start,$end); if ($days<=0) { throw ValidationException::withMessages(['start_date'=>'Rentang cuti tidak memiliki hari kerja.']); } if (LeaveRequest::where('employee_id',$employee->id)->whereNotIn('status',[LeaveStatus::Rejected,LeaveStatus::Cancelled])->where('start_date','<=',$end)->where('end_date','>=',$start)->exists()) { throw ValidationException::withMessages(['start_date'=>'Rentang cuti bertumpang tindih.']); } $balance=LeaveBalance::where('employee_id',$employee->id)->where('leave_type_id',$data['leave_type_id'])->where('year',$start->year)->lockForUpdate()->firstOrFail(); if ((float)$balance->entitled-(float)$balance->used-(float)$balance->pending<$days) { throw ValidationException::withMessages(['leave_type_id'=>'Saldo cuti tidak mencukupi.']); } $balance->increment('pending',$days); return LeaveRequest::create([...$data,'employee_id'=>$employee->id,'days'=>$days]); }); } }
