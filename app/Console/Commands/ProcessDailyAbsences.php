<?php

namespace App\Console\Commands;

use App\Enums\LeaveStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ProcessDailyAbsences extends Command
{
    protected $signature = 'nusahr:process-absences {date?}';
    protected $description = 'Catat ketidakhadiran harian secara idempoten';

    public function handle(): int
    {
        $date = Carbon::parse($this->argument('date') ?? today())->startOfDay();
        if ($date->isWeekend() || Holiday::query()->where('date', $date)->exists()) { return self::SUCCESS; }

        Attendance::query()->where('date', $date)->whereNotNull('checked_in_at')->whereNull('checked_out_at')->update(['status' => 'incomplete']);
        Employee::query()->whereDate('joined_at', '<=', $date)->where(fn ($query) => $query->whereNull('ended_at')->orWhereDate('ended_at', '>=', $date))->each(function (Employee $employee) use ($date): void {
            $onLeave = LeaveRequest::query()->where('employee_id', $employee->id)->where('status', LeaveStatus::Approved)->where('start_date', '<=', $date)->where('end_date', '>=', $date)->exists();
            Attendance::firstOrCreate(['employee_id' => $employee->id, 'date' => $date->toDateString()], ['status' => $onLeave ? 'leave' : 'absent']);
        });

        return self::SUCCESS;
    }
}
