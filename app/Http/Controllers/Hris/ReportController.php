<?php

namespace App\Http\Controllers\Hris;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeReport($request);
        $this->auditExport($request, 'employees');

        return Inertia::render('hris/reports', [
            'payrollPeriods' => \App\Models\PayrollPeriod::query()->latest('ends_on')->get(['id', 'name']),
        ]);
    }
    public function employees(Request $request): StreamedResponse
    {
        $this->authorizeReport($request);
        return $this->csv('employees.csv', ['Employee Number','Name','Email','Department','Position','Joined'], Employee::query()->with(['user','department','position'])->orderBy('employee_number'), fn ($employee) => [$employee->employee_number,$employee->user->name,$employee->user->email,$employee->department->name,$employee->position->name,$employee->joined_at->toDateString()]);
    }

    public function attendance(Request $request): StreamedResponse
    {
        $this->authorizeReport($request); $dates = $request->validate(['from' => ['required','date'], 'to' => ['required','date','after_or_equal:from']]);
        $this->auditExport($request, 'attendance', $dates);
        return $this->csv('attendance.csv', ['Employee Number','Name','Date','Check In','Check Out','Minutes','Late','Status'], Attendance::query()->with('employee.user')->whereBetween('date', [$dates['from'],$dates['to']])->orderBy('date'), fn ($row) => [$row->employee->employee_number,$row->employee->user->name,$row->date->toDateString(),$row->checked_in_at,$row->checked_out_at,$row->worked_minutes,$row->late_minutes,$row->status]);
    }

    public function leave(Request $request): StreamedResponse
    {
        $this->authorizeReport($request); $dates = $request->validate(['from' => ['required','date'], 'to' => ['required','date','after_or_equal:from']]);
        $this->auditExport($request, 'leave', $dates);
        return $this->csv('leave.csv', ['Employee Number','Name','Type','Start','End','Days','Status'], LeaveRequest::query()->with(['employee.user','leaveType'])->where('start_date','<=',$dates['to'])->where('end_date','>=',$dates['from'])->orderBy('start_date'), fn ($row) => [$row->employee->employee_number,$row->employee->user->name,$row->leaveType->name,$row->start_date->toDateString(),$row->end_date->toDateString(),$row->days,$row->status->value]);
    }

    public function payroll(Request $request): StreamedResponse
    {
        $this->authorizeReport($request); $data = $request->validate(['period_id' => ['required','exists:payroll_periods,id']]);
        $this->auditExport($request, 'payroll', $data);
        return $this->csv('payroll.csv', ['Employee Number','Name','Basic','Earnings','Deductions','Net'], PayrollRecord::query()->with('employee.user')->where('payroll_period_id',$data['period_id'])->orderBy('employee_id'), fn ($row) => [$row->employee->employee_number,$row->employee->user->name,$row->basic_salary,$row->earnings,$row->deductions,$row->net_salary]);
    }

    private function authorizeReport(Request $request): void { abort_unless($request->user()->isAdministrator(), 403); }

    private function auditExport(Request $request, string $report, array $filters = []): void
    {
        AuditLog::create(['user_id' => $request->user()->id, 'event' => 'report.exported', 'metadata' => ['report' => $report, 'filters' => $filters], 'ip_address' => $request->ip()]);
    }

    private function csv(string $filename, array $header, Builder $query, callable $map): StreamedResponse
    {
        return response()->streamDownload(function () use ($header,$query,$map): void { $out=fopen('php://output','w'); fputcsv($out,$header); $query->chunk(500,fn($rows)=>$rows->each(fn($row)=>fputcsv($out,$map($row)))); fclose($out); },$filename,['Content-Type'=>'text/csv']);
    }
}
