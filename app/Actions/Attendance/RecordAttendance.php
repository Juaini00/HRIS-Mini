<?php
namespace App\Actions\Attendance;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class RecordAttendance { public function checkIn(Employee $employee): Attendance { return DB::transaction(function () use ($employee) { if (Attendance::where('employee_id',$employee->id)->where('date',today())->lockForUpdate()->exists()) { throw ValidationException::withMessages(['attendance'=>'Anda sudah check-in hari ini.']); } $now=now(); return Attendance::create(['employee_id'=>$employee->id,'date'=>$now->toDateString(),'checked_in_at'=>$now,'late_minutes'=>max(0,$now->diffInMinutes($now->copy()->setTime(8,0),false)*-1),'status'=>'present']); }); } public function checkOut(Employee $employee): Attendance { return DB::transaction(function () use ($employee) { $attendance=Attendance::where('employee_id',$employee->id)->where('date',today())->lockForUpdate()->first(); if (!$attendance || $attendance->checked_out_at) { throw ValidationException::withMessages(['attendance'=>'Data check-in tidak ditemukan atau sudah selesai.']); } $now=now(); $attendance->update(['checked_out_at'=>$now,'worked_minutes'=>$attendance->checked_in_at->diffInMinutes($now)]); return $attendance->refresh(); }); } }
