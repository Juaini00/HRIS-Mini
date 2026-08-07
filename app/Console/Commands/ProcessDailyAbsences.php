<?php

namespace App\Console\Commands;

use App\Enums\LeaveRequestStatus;
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
        $day = $date->toDateString();
        if ($date->isWeekend() || Holiday::query()->where('date', $day)->exists()) {
            return self::SUCCESS;
        }

        Attendance::query()->where('date', $day)->whereNotNull('checked_in_at')->whereNull('checked_out_at')->update(['status' => 'incomplete']);
        Employee::query()->whereDate('joined_at', '<=', $day)->where(fn ($query) => $query->whereNull('ended_at')->orWhereDate('ended_at', '>=', $day))->each(function (Employee $employee) use ($day): void {
            $onLeave = LeaveRequest::query()->where('employee_id', $employee->id)->where('status', LeaveRequestStatus::Approved)->where('start_date', '<=', $day)->where('end_date', '>=', $day)->exists();
            Attendance::firstOrCreate(['employee_id' => $employee->id, 'date' => $day], ['status' => $onLeave ? 'leave' : 'absent']);
        });

        return self::SUCCESS;
    }
}
