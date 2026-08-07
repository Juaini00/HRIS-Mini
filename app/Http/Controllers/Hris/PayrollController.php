<?php
namespace App\Http\Controllers\Hris;
use App\Actions\Payroll\GeneratePayrollPeriod;
use App\Enums\PayrollStatus;
use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
class PayrollController extends Controller { public function index(Request $request): Response { $user=$request->user(); $query=PayrollPeriod::with(['records'=>fn($q)=>$user->isAdministrator()?$q->with('employee.user'):$q->where('employee_id',$user->employee?->id??0)]); if(!$user->isAdministrator()){$query->where('status',PayrollStatus::Published);} return Inertia::render('hris/payroll',['periods'=>$query->latest('ends_on')->paginate(12),'canManage'=>$user->isAdministrator()]); } public function store(Request $request,GeneratePayrollPeriod $generate): RedirectResponse { abort_unless($request->user()->isAdministrator(),403); $data=$request->validate(['name'=>['required','string','max:100'],'starts_on'=>['required','date'],'ends_on'=>['required','date','after_or_equal:starts_on']]); $generate->handle($data); return back()->with('success','Payroll berhasil dibuat.'); } public function publish(Request $request,PayrollPeriod $payrollPeriod): RedirectResponse { abort_unless($request->user()->isAdministrator(),403); DB::transaction(function()use($request,$payrollPeriod){$period=PayrollPeriod::lockForUpdate()->findOrFail($payrollPeriod->id); abort_if($period->status===PayrollStatus::Published,422); $period->update(['status'=>PayrollStatus::Published,'published_at'=>now(),'published_by'=>$request->user()->id]);}); return back()->with('success','Payroll dipublikasikan.'); } }
