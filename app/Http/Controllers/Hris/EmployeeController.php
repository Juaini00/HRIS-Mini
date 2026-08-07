<?php
namespace App\Http\Controllers\Hris;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
class EmployeeController extends Controller { public function index(Request $request): Response { abort_unless($request->user()->isAdministrator(),403); return Inertia::render('hris/employees',['employees'=>Employee::with(['user','department','position','manager.user'])->latest()->paginate(20),'departments'=>Department::where('is_active',true)->get(),'positions'=>Position::where('is_active',true)->get(),'locations'=>Location::where('is_active',true)->get()]); } public function store(Request $request): RedirectResponse { abort_unless($request->user()->isAdministrator(),403); $data=$request->validate(['name'=>['required','string','max:100'],'email'=>['required','email','unique:users,email'],'employee_number'=>['required','string','max:30','unique:employees,employee_number'],'department_id'=>['required',Rule::exists('departments','id')->where('is_active',true)],'position_id'=>['required',Rule::exists('positions','id')->where('is_active',true)],'location_id'=>['nullable',Rule::exists('locations','id')->where('is_active',true)],'joined_at'=>['required','date'],'basic_salary'=>['required','numeric','min:0'],'role'=>['required',Rule::enum(UserRole::class)]]); DB::transaction(function () use ($data) { $user=User::create(['name'=>$data['name'],'email'=>$data['email'],'password'=>Hash::make('NusaHR123!'),'role'=>$data['role'],'email_verified_at'=>now()]); $user->employee()->create(collect($data)->except(['name','email','role'])->all()); }); return back()->with('success','Karyawan berhasil ditambahkan.'); } }
