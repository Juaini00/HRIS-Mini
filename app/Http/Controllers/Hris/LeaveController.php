<?php
namespace App\Http\Controllers\Hris;
use App\Actions\Leave\ReviewLeaveRequest;
use App\Actions\Leave\SubmitLeaveRequest;
use App\Enums\LeaveStatus;
use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
class LeaveController extends Controller { public function index(Request $request): Response { $employee=$request->user()->employee; return Inertia::render('hris/leave',['requests'=>$request->user()->isAdministrator()?LeaveRequest::with(['employee.user','leaveType'])->latest()->paginate(20):$employee?->leaveRequests()->with('leaveType')->latest()->paginate(20),'types'=>LeaveType::where('is_active',true)->get(),'balances'=>$employee?LeaveBalance::with('leaveType')->where('employee_id',$employee->id)->where('year',now()->year)->get():[],'canReview'=>$request->user()->isAdministrator()||$request->user()->role===\App\Enums\UserRole::Manager]); } public function store(Request $request,SubmitLeaveRequest $submit): RedirectResponse { $employee=$request->user()->employee; abort_unless($employee,403); $data=$request->validate(['leave_type_id'=>['required',Rule::exists('leave_types','id')->where('is_active',true)],'start_date'=>['required','date','after_or_equal:today'],'end_date'=>['required','date','after_or_equal:start_date'],'reason'=>['required','string','max:1000']]); $submit->handle($employee,$data); return back()->with('success','Permintaan cuti dikirim.'); } public function review(Request $request,LeaveRequest $leaveRequest,ReviewLeaveRequest $review): RedirectResponse { abort_unless($request->user()->isAdministrator()||$leaveRequest->employee->manager?->user_id===$request->user()->id,403); $data=$request->validate(['status'=>['required',Rule::enum(LeaveStatus::class)],'notes'=>['nullable','string','max:1000']]); $review->handle($leaveRequest,$request->user(),LeaveStatus::from($data['status']),$data['notes']??null); return back()->with('success','Permintaan cuti diproses.'); } }
