<?php
namespace App\Http\Controllers\Hris;
use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
class PayslipController extends Controller { public function show(Request $request, PayrollRecord $payrollRecord): Response { Gate::authorize('view',$payrollRecord); return Inertia::render('hris/payslip',['record'=>$payrollRecord->load(['period','employee.user','employee.department','employee.position','items']),'company'=>\App\Models\Setting::whereIn('key',['company_name','currency'])->pluck('value','key')]); } }
