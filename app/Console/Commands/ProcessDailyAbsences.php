<?php
namespace App\Console\Commands;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
class ProcessDailyAbsences extends Command { protected $signature='nusahr:process-absences {date?}'; protected $description='Catat ketidakhadiran harian secara idempoten'; public function handle(): int { $date=Carbon::parse($this->argument('date') ?? today())->startOfDay(); if($date->isWeekend()||Holiday::where('date',$date)->exists()){return self::SUCCESS;} Employee::whereNull('ended_at')->each(function(Employee $employee)use($date){$onLeave=LeaveRequest::where('employee_id',$employee->id)->where('status','approved')->where('start_date','<=',$date)->where('end_date','>=',$date)->exists(); if(!$onLeave){Attendance::firstOrCreate(['employee_id'=>$employee->id,'date'=>$date->toDateString()],['status'=>'absent']);}}); return self::SUCCESS; } }
