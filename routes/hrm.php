<?php

/*
| HRM web routes. Loaded from routes/web.php inside the auth + tenant group.
| `module:hrm` = tenant has HRM enabled; each controller then checks the
| user's HRM permission (hrm.view_employees, hrm.process_payroll, …).
*/

use App\Http\Controllers\Hrm;
use Illuminate\Support\Facades\Route;

Route::prefix('hrm')->name('hrm.')->middleware('module:hrm')->group(function () {
    // ── My HR (self-service: own data only) ──
    Route::middleware('module:hrm,my-profile')->group(function () {
        Route::get('me', [Hrm\SelfServiceController::class, 'profile'])->name('me.profile');
        Route::patch('me', [Hrm\SelfServiceController::class, 'updateProfile'])->name('me.profile.update');
    });
    Route::middleware('module:hrm,my-attendance')->group(function () {
        Route::get('me/attendance', [Hrm\SelfServiceController::class, 'attendance'])->name('me.attendance');
        Route::post('me/check-in', [Hrm\SelfServiceController::class, 'checkIn'])->name('me.check-in');
        Route::post('me/check-out', [Hrm\SelfServiceController::class, 'checkOut'])->name('me.check-out');
    });
    Route::middleware('module:hrm,my-leaves')->group(function () {
        Route::get('me/leaves', [Hrm\SelfServiceController::class, 'leaves'])->name('me.leaves');
        Route::post('me/leaves', [Hrm\SelfServiceController::class, 'applyLeave'])->name('me.leaves.apply');
        Route::post('me/leaves/{leave}/cancel', [Hrm\SelfServiceController::class, 'cancelLeave'])->name('me.leaves.cancel');
    });
    Route::get('me/payslips', [Hrm\SelfServiceController::class, 'payslips'])->middleware('module:hrm,my-payslips')->name('me.payslips');

    // ── Employees & organization ──
    Route::middleware('module:hrm,employees')->group(function () {
        Route::resource('employees', Hrm\EmployeeController::class);
        Route::post('employees/{employee}/documents', [Hrm\EmployeeController::class, 'storeDocument'])->name('employees.documents.store');
        Route::delete('employees/{employee}/documents/{document}', [Hrm\EmployeeController::class, 'destroyDocument'])->name('employees.documents.destroy');
    });
    // Own documents are downloadable from My Profile too (checked in controller).
    Route::get('employees/{employee}/documents/{document}', [Hrm\EmployeeController::class, 'downloadDocument'])->name('employees.documents.download');

    Route::get('org-chart', Hrm\OrgChartController::class)->middleware('module:hrm,org-chart')->name('org-chart');
    Route::resource('departments', Hrm\DepartmentController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('module:hrm,departments');
    Route::resource('designations', Hrm\DesignationController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('module:hrm,designations');

    // ── Attendance ──
    Route::middleware('module:hrm,attendance')->group(function () {
        Route::get('attendance', [Hrm\AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('attendance', [Hrm\AttendanceController::class, 'store'])->name('attendance.store');
        Route::post('attendance/mark-all', [Hrm\AttendanceController::class, 'markAllPresent'])->name('attendance.mark-all');
        Route::get('attendance/month', [Hrm\AttendanceController::class, 'month'])->name('attendance.month');
    });

    // ── Leave ──
    Route::middleware('module:hrm,leave-requests')->group(function () {
        Route::get('leaves', [Hrm\LeaveRequestController::class, 'index'])->name('leaves.index');
        Route::post('leaves', [Hrm\LeaveRequestController::class, 'store'])->name('leaves.store');
        Route::post('leaves/{leave}/approve', [Hrm\LeaveRequestController::class, 'approve'])->name('leaves.approve');
        Route::post('leaves/{leave}/reject', [Hrm\LeaveRequestController::class, 'reject'])->name('leaves.reject');
    });
    Route::resource('leave-types', Hrm\LeaveTypeController::class)->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['leave-types' => 'leaveType'])->middleware('module:hrm,leave-types');
    Route::middleware('module:hrm,leave-allocations')->group(function () {
        Route::get('allocations', [Hrm\AllocationController::class, 'index'])->name('allocations.index');
        Route::post('allocations/allocate', [Hrm\AllocationController::class, 'allocate'])->name('allocations.allocate');
        Route::put('allocations/{employee}', [Hrm\AllocationController::class, 'update'])->name('allocations.update');
    });
    Route::resource('holidays', Hrm\HolidayController::class)->only(['index', 'store', 'destroy'])->middleware('module:hrm,holidays');

    // ── Payroll ──
    Route::middleware('module:hrm,payroll')->group(function () {
        Route::get('payroll', [Hrm\PayrollController::class, 'index'])->name('payroll.index');
        Route::post('payroll', [Hrm\PayrollController::class, 'generate'])->name('payroll.generate');
        Route::get('payroll/{run}', [Hrm\PayrollController::class, 'show'])->name('payroll.show');
        Route::post('payroll/{run}/finalize', [Hrm\PayrollController::class, 'finalize'])->name('payroll.finalize');
        Route::post('payroll/{run}/paid', [Hrm\PayrollController::class, 'markPaid'])->name('payroll.paid');
        Route::delete('payroll/{run}', [Hrm\PayrollController::class, 'destroy'])->name('payroll.destroy');
        Route::put('payslips/{payslip}', [Hrm\PayrollController::class, 'updatePayslip'])->name('payslips.update');
    });
    // Printable payslip: HR or the employee themself (checked in controller).
    Route::get('payslips/{payslip}', [Hrm\PayrollController::class, 'payslip'])->name('payslips.show');

    // ── Recruitment ──
    Route::resource('jobs', Hrm\JobController::class)->except('show')->middleware('module:hrm,jobs');
    Route::middleware('module:hrm,applications')->group(function () {
        Route::get('applications', [Hrm\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('applications/{application}', [Hrm\ApplicationController::class, 'show'])->name('applications.show');
        Route::put('applications/{application}', [Hrm\ApplicationController::class, 'update'])->name('applications.update');
        Route::get('applications/{application}/resume', [Hrm\ApplicationController::class, 'resume'])->name('applications.resume');
        Route::delete('applications/{application}', [Hrm\ApplicationController::class, 'destroy'])->name('applications.destroy');
    });

    Route::get('reports', Hrm\ReportController::class)->middleware('module:hrm,reports')->name('reports');
});
