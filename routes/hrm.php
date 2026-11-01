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
    Route::middleware('module:hrm,my-interviews')->group(function () {
        Route::get('me/interviews', [Hrm\SelfServiceController::class, 'interviews'])->name('me.interviews');
        Route::get('me/interviews/{application}', [Hrm\SelfServiceController::class, 'interview'])->name('me.interviews.show');
        Route::put('me/interviews/{application}', [Hrm\SelfServiceController::class, 'interviewFeedback'])->name('me.interviews.feedback');
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
        Route::delete('applications/{application}', [Hrm\ApplicationController::class, 'destroy'])->name('applications.destroy');
    });

    // Resume: HR or the assigned interviewer (checked in controller).
    Route::get('applications/{application}/resume', [Hrm\ApplicationController::class, 'resume'])->name('applications.resume');

    // ── v2: dashboard, team, shifts, loans, reviews, lifecycle, announcements ──
    Route::get('dashboard', Hrm\DashboardController::class)->middleware('module:hrm,dashboard')->name('dashboard');

    Route::middleware('module:hrm,my-team')->group(function () {
        Route::get('me/team', [Hrm\TeamController::class, 'index'])->name('team');
        Route::post('me/team/leaves/{leave}/{decision}', [Hrm\TeamController::class, 'decide'])
            ->whereIn('decision', ['approve', 'reject'])->name('team.leaves.decide');
    });

    Route::middleware('module:hrm,my-reviews')->group(function () {
        Route::get('me/reviews', [Hrm\ReviewController::class, 'mine'])->name('me.reviews');
        Route::post('me/reviews/{review}/acknowledge', [Hrm\ReviewController::class, 'acknowledge'])->name('me.reviews.acknowledge');
    });
    // HR (hrm.manage_performance) or the employee's manager — checked in controller.
    Route::resource('reviews', Hrm\ReviewController::class)->middleware('module:hrm,reviews');

    Route::resource('shifts', Hrm\ShiftController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('module:hrm,shifts');

    Route::middleware('module:hrm,loans')->group(function () {
        Route::get('loans', [Hrm\LoanController::class, 'index'])->name('loans.index');
        Route::post('loans', [Hrm\LoanController::class, 'store'])->name('loans.store');
        Route::post('loans/{loan}/close', [Hrm\LoanController::class, 'close'])->name('loans.close');
        Route::post('employees/{employee}/components', [Hrm\LoanController::class, 'storeComponent'])->name('components.store');
        Route::delete('employees/{employee}/components/{component}', [Hrm\LoanController::class, 'destroyComponent'])->name('components.destroy');
    });

    Route::middleware('module:hrm,lifecycle')->group(function () {
        Route::get('lifecycle', [Hrm\LifecycleController::class, 'index'])->name('lifecycle.index');
        Route::post('lifecycle/onboarding/{employee}', [Hrm\LifecycleController::class, 'startOnboarding'])->name('lifecycle.onboarding');
        Route::post('lifecycle/items/{item}/toggle', [Hrm\LifecycleController::class, 'toggle'])->name('lifecycle.items.toggle');
        Route::post('lifecycle/{employee}/items', [Hrm\LifecycleController::class, 'addItem'])->name('lifecycle.items.store');
        Route::post('lifecycle/{employee}/exit', [Hrm\LifecycleController::class, 'startExit'])->name('lifecycle.exit.start');
        Route::post('lifecycle/exits/{exit}/complete', [Hrm\LifecycleController::class, 'completeExit'])->name('lifecycle.exit.complete');
        Route::post('lifecycle/exits/{exit}/withdraw', [Hrm\LifecycleController::class, 'withdrawExit'])->name('lifecycle.exit.withdraw');
    });

    Route::resource('announcements', Hrm\AnnouncementController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('module:hrm,announcements');

    Route::get('reports', Hrm\ReportController::class)->middleware('module:hrm,reports')->name('reports');
});
