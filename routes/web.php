<?php

use App\Http\Controllers\Hris\AnnouncementController;
use App\Http\Controllers\Hris\AttendanceController;
use App\Http\Controllers\Hris\AuditLogController;
use App\Http\Controllers\Hris\CompanySettingController;
use App\Http\Controllers\Hris\DashboardController;
use App\Http\Controllers\Hris\EmployeeController;
use App\Http\Controllers\Hris\EmployeeDocumentController;
use App\Http\Controllers\Hris\LeaveController;
use App\Http\Controllers\Hris\NotificationController;
use App\Http\Controllers\Hris\OrganizationController;
use App\Http\Controllers\Hris\PayrollController;
use App\Http\Controllers\Hris\PayslipController;
use App\Http\Controllers\Hris\ReportController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('employees', EmployeeController::class)->only(['index', 'show', 'store', 'update']);
    Route::patch('employees/{employee}/deactivate', [EmployeeController::class, 'deactivate'])->name('employees.deactivate');
    Route::post('employees/{employee}/photo', [EmployeeController::class, 'updatePhoto'])->name('employees.photo.update');
    Route::get('leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::post('leave', [LeaveController::class, 'store'])->name('leave.store');
    Route::patch('leave/{leaveRequest}/review', [LeaveController::class, 'review'])->name('leave.review');
    Route::patch('leave/{leaveRequest}/cancel', [LeaveController::class, 'cancel'])->name('leave.cancel');
    Route::get('leave/{leaveRequest}/attachment', [LeaveController::class, 'attachment'])->name('leave.attachment');
    Route::post('leave-balances/adjust', [LeaveController::class, 'adjustBalance'])->name('leave-balances.adjust');
    Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance/check-in', [AttendanceController::class, 'checkIn'])->name('attendance.check-in');
    Route::post('attendance/check-out', [AttendanceController::class, 'checkOut'])->name('attendance.check-out');
    Route::patch('attendance/{attendance}/correct', [AttendanceController::class, 'correct'])->name('attendance.correct');
    Route::post('attendance/{attendance}/correction-requests', [AttendanceController::class, 'requestCorrection'])->name('attendance.corrections.store');
    Route::patch('attendance-corrections/{attendanceCorrection}', [AttendanceController::class, 'reviewCorrection'])->name('attendance.corrections.review');
    Route::resource('payroll', PayrollController::class)->only(['index', 'store'])->parameters(['payroll' => 'payrollPeriod']);
    Route::post('payroll/{payrollPeriod}/publish', [PayrollController::class, 'publish'])->name('payroll.publish');
    Route::post('payroll-records/{payrollRecord}/adjustments', [PayrollController::class, 'adjustment'])->name('payroll.adjustments.store');
    Route::post('salary-components', [PayrollController::class, 'component'])->name('salary-components.store');
    Route::post('salary-components/{salaryComponent}/assign', [PayrollController::class, 'assignComponent'])->name('salary-components.assign');
    Route::get('payroll/{payrollPeriod}/export', [PayrollController::class, 'export'])->name('payroll.export');
    Route::get('payslips/{payrollRecord}', [PayslipController::class, 'show'])->name('payslips.show');
    Route::resource('announcements', AnnouncementController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::post('announcements/{announcement}/read', [AnnouncementController::class, 'read'])->name('announcements.read');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/{report}/export', [ReportController::class, 'export'])
        ->whereIn('report', array_keys(ReportController::catalogue()))
        ->name('reports.export');
    Route::post('employees/{employee}/documents', [EmployeeDocumentController::class, 'store'])->name('employee-documents.store');
    Route::get('employee-documents/{employeeDocument}', [EmployeeDocumentController::class, 'show'])->name('employee-documents.show');
    Route::delete('employee-documents/{employeeDocument}', [EmployeeDocumentController::class, 'destroy'])->name('employee-documents.destroy');
    Route::get('organization', [OrganizationController::class, 'index'])->name('organization.index');
    Route::prefix('organization')->name('organization.')->group(function (): void {
        $entities = implode('|', array_keys(OrganizationController::entities()));

        Route::get('{entity}', [OrganizationController::class, 'index'])->where('entity', $entities)->name('show');
        Route::post('{entity}', [OrganizationController::class, 'store'])->where('entity', $entities)->name('store');
        Route::put('{entity}/{id}', [OrganizationController::class, 'update'])->where('entity', $entities)->name('update');
    });
    Route::get('company-settings', [CompanySettingController::class, 'edit'])->name('company-settings.edit');
    Route::put('company-settings', [CompanySettingController::class, 'update'])->name('company-settings.update');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

require __DIR__.'/settings.php';
