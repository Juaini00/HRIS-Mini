<?php
namespace App\Http\Controllers\Hris;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
class ReportController extends Controller { public function employees(Request $request): StreamedResponse { abort_unless($request->user()->isAdministrator(),403); return response()->streamDownload(function(){ $out=fopen('php://output','w'); fputcsv($out,['Employee Number','Name','Email','Department','Position','Joined']); Employee::with(['user','department','position'])->orderBy('employee_number')->chunk(500,fn($employees)=>$employees->each(fn($employee)=>fputcsv($out,[$employee->employee_number,$employee->user->name,$employee->user->email,$employee->department->name,$employee->position->name,$employee->joined_at->toDateString()]))); fclose($out); },'employees.csv',['Content-Type'=>'text/csv']); } }
