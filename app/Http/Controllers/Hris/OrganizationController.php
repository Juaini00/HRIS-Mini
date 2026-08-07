<?php

namespace App\Http\Controllers\Hris;

use App\Actions\Audit\WriteAuditLog;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\EmploymentType;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->isAdministrator(), 403);
        return Inertia::render('hris/organization', ['departments' => Department::withCount('employees')->orderBy('name')->get(), 'positions' => Position::with('department')->orderBy('name')->get(), 'locations' => Location::orderBy('name')->get(), 'employmentTypes' => EmploymentType::orderBy('name')->get(), 'leaveTypes' => LeaveType::orderBy('name')->get(), 'holidays' => Holiday::orderByDesc('date')->get()]);
    }
    public function department(Request $request, WriteAuditLog $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403); $data=$request->validate(['name'=>['required','string','max:100','unique:departments,name'],'code'=>['required','string','max:20','unique:departments,code'],'is_active'=>['boolean']]); $model=Department::create($data); $audit->handle($request,'department.created',$model); return back()->with('success','Departemen ditambahkan.');
    }
    public function position(Request $request, WriteAuditLog $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403); $data=$request->validate(['department_id'=>['required',Rule::exists('departments','id')->where('is_active',true)],'name'=>['required','string','max:100'],'is_active'=>['boolean']]); $model=Position::create($data); $audit->handle($request,'position.created',$model); return back()->with('success','Posisi ditambahkan.');
    }
    public function location(Request $request, WriteAuditLog $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403); $data=$request->validate(['name'=>['required','string','max:100','unique:locations,name'],'timezone'=>['required','timezone'],'is_active'=>['boolean']]); $model=Location::create($data); $audit->handle($request,'location.created',$model); return back()->with('success','Lokasi ditambahkan.');
    }
    public function leaveType(Request $request, WriteAuditLog $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403); $data=$request->validate(['name'=>['required','string','max:100','unique:leave_types,name'],'annual_quota'=>['required','integer','min:0','max:365'],'is_paid'=>['boolean'],'requires_attachment'=>['boolean'],'is_active'=>['boolean']]); $model=LeaveType::create($data); $audit->handle($request,'leave-type.created',$model); return back()->with('success','Jenis cuti ditambahkan.');
    }
    public function employmentType(Request $request, WriteAuditLog $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403); $data=$request->validate(['name'=>['required','string','max:100','unique:employment_types,name'],'is_active'=>['boolean']]); $model=EmploymentType::create($data); $audit->handle($request,'employment-type.created',$model); return back()->with('success','Tipe kepegawaian ditambahkan.');
    }
    public function holiday(Request $request, WriteAuditLog $audit): RedirectResponse
    {
        abort_unless($request->user()->isAdministrator(), 403); $data=$request->validate(['date'=>['required','date','unique:holidays,date'],'name'=>['required','string','max:150']]); $model=Holiday::create($data); $audit->handle($request,'holiday.created',$model); return back()->with('success','Hari libur ditambahkan.');
    }
}
