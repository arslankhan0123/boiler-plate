<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Announcement;
use App\Models\Hrm\Attendance;
use App\Models\Hrm\EmployeeExit;
use App\Models\Hrm\Job;
use App\Models\Hrm\JobApplication;
use App\Models\Hrm\LeaveRequest;
use App\Models\Hrm\Review;
use Illuminate\Support\Carbon;
use Modules\Setup\Models\Employee;
use Modules\Setup\Models\EmployeeDocument;

/** HR overview: everything that needs attention today. */
class DashboardController extends HrmController
{
    public function __invoke()
    {
        $this->need('hrm.view_employees');
        $user = auth()->user();

        $active = Employee::query()->with(['designation', 'department'])->where('is_active', true)->get();
        $today = Attendance::query()->whereDate('date', today())->get();
        $isWorkday = ! in_array(today()->dayOfWeek, config('hrm.weekend_days'), true);

        // Birthdays & work anniversaries in the next 30 days (year-agnostic).
        $upcoming = function (string $field) use ($active) {
            return $active->filter(fn ($e) => $e->{$field})->map(function ($e) use ($field) {
                $d = Carbon::parse($e->{$field});
                $next = $d->copy()->year(today()->year);
                if ($next->lt(today())) {
                    $next->addYear();
                }
                $e->next_date = $next;
                $e->years = $next->year - $d->year;

                return $e;
            })->filter(fn ($e) => $e->next_date->lte(today()->addDays(30)) && ($field !== 'join_date' || $e->years > 0))
                ->sortBy('next_date')->take(8)->values();
        };

        return view('hrm.dashboard', [
            'stats' => [
                'employees' => $active->count(),
                'present' => $today->whereIn('status', ['present', 'late', 'half_day'])->count(),
                'late' => $today->where('status', 'late')->count(),
                'on_leave' => $today->where('status', 'on_leave')->count(),
                'not_marked' => $isWorkday ? max(0, $active->count() - $today->count()) : 0,
                'joined_month' => $active->filter(fn ($e) => $e->join_date && Carbon::parse($e->join_date)->isSameMonth(today()))->count(),
            ],
            'pendingLeaves' => $user->can('hrm.approve_leave')
                ? LeaveRequest::query()->with(['employee', 'type'])->where('status', 'pending')->orderBy('start_date')->limit(6)->get() : collect(),
            'onLeaveToday' => LeaveRequest::query()->with(['employee', 'type'])->where('status', 'approved')
                ->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->get(),
            'birthdays' => $upcoming('dob'),
            'anniversaries' => $upcoming('join_date'),
            'expiringDocs' => EmployeeDocument::query()->with('employee')->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', today()->addDays(30))->orderBy('expiry_date')->limit(8)->get()
                ->filter(fn ($d) => $d->employee),
            'interviews' => $user->can('hrm.manage_recruitment')
                ? JobApplication::query()->with(['job', 'interviewer'])->whereNotNull('interview_at')
                    ->whereBetween('interview_at', [now()->startOfDay(), now()->addDays(7)->endOfDay()])->orderBy('interview_at')->limit(6)->get() : collect(),
            'recruitment' => [
                'open_jobs' => Job::query()->where('status', 'open')->count(),
                'new_applications' => JobApplication::query()->where('status', 'new')->count(),
            ],
            'exits' => $user->can('hrm.manage_lifecycle')
                ? EmployeeExit::query()->with('employee')->where('status', 'in_progress')->orderBy('last_working_day')->limit(5)->get() : collect(),
            'reviewsPending' => $user->can('hrm.manage_performance') ? Review::query()->where('status', 'shared')->count() : 0,
            'byDepartment' => $active->groupBy(fn ($e) => $e->department->name ?? 'No department')->map->count()->sortDesc(),
            'announcements' => Announcement::query()->visibleTo(null)->orderByDesc('is_pinned')->latest()->limit(3)->get(),
        ]);
    }
}
