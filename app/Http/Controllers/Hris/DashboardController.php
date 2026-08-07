<?php
namespace App\Http\Controllers\Hris;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class DashboardController extends Controller { public function __invoke(Request $request): Response { $employee=$request->user()->employee; return Inertia::render('dashboard',['stats'=>['employees'=>Employee::whereNull('ended_at')->count(),'departments'=>Department::where('is_active',true)->count(),'pendingLeave'=>LeaveRequest::where('status','pending')->count(),'presentToday'=>Attendance::where('date',today())->count()],'myAttendance'=>$employee?->attendances()->where('date',today())->first(),'myLeave'=>$employee?->leaveRequests()->latest()->limit(5)->with('leaveType')->get()??[],'announcements'=>Announcement::whereNotNull('published_at')->where('published_at','<=',now())->whereIn('audience',['all',$request->user()->role->value])->latest('published_at')->limit(5)->get(),'payrollPeriods'=>PayrollPeriod::where('status','published')->latest('ends_on')->limit(3)->get()]); } }
