<?php
namespace App\Http\Controllers\Hris;
use App\Actions\Attendance\RecordAttendance;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
class AttendanceController extends Controller { public function index(Request $request): Response { $employee=$request->user()->employee; $query=Attendance::with('employee.user')->latest('date'); if(!$request->user()->isAdministrator()){$query->where('employee_id',$employee?->id??0);} return Inertia::render('hris/attendance',['attendances'=>$query->paginate(25),'today'=>$employee?->attendances()->where('date',today())->first()]); } public function checkIn(Request $request,RecordAttendance $record): RedirectResponse { abort_unless($request->user()->employee,403); $record->checkIn($request->user()->employee); return back()->with('success','Check-in berhasil.'); } public function checkOut(Request $request,RecordAttendance $record): RedirectResponse { abort_unless($request->user()->employee,403); $record->checkOut($request->user()->employee); return back()->with('success','Check-out berhasil.'); } }
