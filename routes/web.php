<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Hris\AnnouncementController;
use App\Http\Controllers\Hris\AttendanceController;
use App\Http\Controllers\Hris\DashboardController;
use App\Http\Controllers\Hris\EmployeeController;
use App\Http\Controllers\Hris\LeaveController;
use App\Http\Controllers\Hris\PayrollController;
use App\Http\Controllers\Hris\ReportController;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('employees', EmployeeController::class)->only(['index', 'store']);
    Route::get('leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::post('leave', [LeaveController::class, 'store'])->name('leave.store');
    Route::patch('leave/{leaveRequest}/review', [LeaveController::class, 'review'])->name('leave.review');
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
    Route::resource('payroll', PayrollController::class)->only(['index', 'store'])->parameters(['payroll' => 'payrollPeriod']);
    Route::post('payroll/{payrollPeriod}/publish', [PayrollController::class, 'publish'])->name('payroll.publish');
    Route::resource('announcements', AnnouncementController::class)->only(['index', 'store']);
    Route::get('reports/employees.csv', [ReportController::class, 'employees'])->name('reports.employees');
});

require __DIR__.'/settings.php';
