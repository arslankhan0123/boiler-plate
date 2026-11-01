<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Attendance;
use App\Models\Hrm\JobApplication;
use App\Models\Hrm\LeaveRequest;
use App\Models\Hrm\PayrollRun;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Setup\Models\Department;
use Modules\Setup\Models\Employee;

class ReportController extends HrmController
{
    public function __invoke(Request $request)
    {
        $this->need('hrm.reports');
        $year = (int) $request->input('year', now()->year);
        $month = Carbon::createFromFormat('Y-m', $request->input('month', now()->format('Y-m')))->startOfMonth();

        $headcount = Department::query()->withCount(['employees' => fn ($q) => $q->where('is_active', true)])->orderByDesc('employees_count')->get();

        $leaveByType = LeaveRequest::query()->join('hrm_leave_types as t', 't.id', '=', 'hrm_leave_requests.leave_type_id')
            ->where('hrm_leave_requests.status', 'approved')->whereYear('start_date', $year)
            ->groupBy('t.name', 't.color')->select('t.name', 't.color', DB::raw('SUM(days) as days'), DB::raw('COUNT(*) as requests'))->get();

        $attendance = Attendance::query()->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->select('status', DB::raw('COUNT(*) c'))->groupBy('status')->pluck('c', 'status');

        $payroll = PayrollRun::query()->where('month', 'like', "{$year}-%")->orderBy('month')->get();

        return view('hrm.reports', [
            'year' => $year,
            'month' => $month,
            'headcount' => $headcount,
            'totalEmployees' => Employee::query()->where('is_active', true)->count(),
            'joinedThisYear' => Employee::query()->whereYear('join_date', $year)->count(),
            'leftThisYear' => Employee::onlyTrashed()->whereYear('deleted_at', $year)->count(),
            'leaveByType' => $leaveByType,
            'attendance' => $attendance,
            'payroll' => $payroll,
            'applications' => JobApplication::query()->whereYear('created_at', $year)->select('status', DB::raw('COUNT(*) c'))->groupBy('status')->pluck('c', 'status'),
        ]);
    }
}
