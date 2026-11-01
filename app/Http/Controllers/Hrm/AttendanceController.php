<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Attendance;
use App\Models\Hrm\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Modules\Setup\Models\Department;
use Modules\Setup\Models\Employee;

class AttendanceController extends HrmController
{
    /** Daily sheet. */
    public function index(Request $request)
    {
        $this->need('hrm.view_attendance');
        $date = Carbon::parse($request->input('date', today()->toDateString()));

        $employees = Employee::query()->with(['department', 'designation'])->where('is_active', true)
            ->whereDate('join_date', '<=', $date)
            ->when($request->filled('department'), fn ($q) => $q->where('department_id', $request->department))
            ->orderBy('name')->get();
        $records = Attendance::query()->whereDate('date', $date)->get()->keyBy('employee_id');

        return view('hrm.attendance.index', [
            'date' => $date,
            'employees' => $employees,
            'records' => $records,
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'holiday' => Holiday::query()->whereDate('date', $date)->first(),
            'isWeekend' => in_array($date->dayOfWeek, config('hrm.weekend_days'), true),
            'summary' => collect(Attendance::STATUSES)->map(fn ($s, $k) => $records->where('status', $k)->count()),
            'canEdit' => auth()->user()->can('hrm.manage_attendance'),
        ]);
    }

    public function store(Request $request)
    {
        $this->need('hrm.manage_attendance');
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'rows' => ['array'],
            'rows.*.status' => ['nullable', Rule::in(array_keys(Attendance::STATUSES))],
            'rows.*.check_in' => ['nullable', 'date_format:H:i'],
            'rows.*.check_out' => ['nullable', 'date_format:H:i'],
            'rows.*.note' => ['nullable', 'string', 'max:190'],
        ]);

        $saved = 0;
        foreach ($data['rows'] ?? [] as $employeeId => $row) {
            if (empty($row['status'])) {
                Attendance::query()->where('employee_id', $employeeId)->whereDate('date', $data['date'])->where('status', '!=', 'on_leave')->delete();

                continue;
            }
            $values = self::compute($row['check_in'] ?? null, $row['check_out'] ?? null, $row['status'],
                \App\Models\Hrm\Shift::forEmployee(Employee::query()->find($employeeId)));
            Attendance::query()->updateOrCreate(
                ['employee_id' => (int) $employeeId, 'date' => $data['date']],
                $values + ['note' => $row['note'] ?? null, 'marked_by' => auth()->id()],
            );
            $saved++;
        }

        return redirect()->route('hrm.attendance.index', ['date' => $data['date']])->with('success', "Attendance saved ({$saved} employees).");
    }

    /** Quick "mark everyone without a record as present". */
    public function markAllPresent(Request $request)
    {
        $this->need('hrm.manage_attendance');
        $date = $request->validate(['date' => ['required', 'date', 'before_or_equal:today']])['date'];
        $done = Attendance::query()->whereDate('date', $date)->pluck('employee_id');
        $n = 0;
        Employee::query()->where('is_active', true)->whereDate('join_date', '<=', $date)->whereNotIn('id', $done)->each(function ($e) use ($date, &$n) {
            $shift = \App\Models\Hrm\Shift::forEmployee($e);
            Attendance::create(['employee_id' => $e->id, 'date' => $date, 'status' => 'present',
                'check_in' => $shift ? substr((string) $shift->start_time, 0, 5) : config('hrm.office_start'), 'marked_by' => auth()->id()]);
            $n++;
        });

        return back()->with('success', "{$n} employees marked present.");
    }

    /** Month grid: every employee × every day. */
    public function month(Request $request)
    {
        $this->need('hrm.view_attendance');
        $month = Carbon::createFromFormat('Y-m', $request->input('month', now()->format('Y-m')))->startOfMonth();

        $employees = Employee::query()->where('is_active', true)
            ->when($request->filled('department'), fn ($q) => $q->where('department_id', $request->department))
            ->orderBy('name')->get();
        $records = Attendance::query()->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->get()->groupBy('employee_id')->map(fn ($rows) => $rows->keyBy(fn ($r) => $r->date->day));

        return view('hrm.attendance.month', [
            'month' => $month,
            'employees' => $employees,
            'records' => $records,
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'holidays' => Holiday::query()->whereYear('date', $month->year)->whereMonth('date', $month->month)->get()->keyBy(fn ($h) => $h->date->day),
        ]);
    }

    /** Status + worked minutes from times (late / half day are automatic). */
    public static function compute(?string $in, ?string $out, string $status = 'present', ?\App\Models\Hrm\Shift $shift = null): array
    {
        $minutes = 0;
        if ($in && $out) {
            $start = Carbon::createFromFormat('H:i', substr($in, 0, 5));
            $end = Carbon::createFromFormat('H:i', substr($out, 0, 5));
            // Check-out is always AFTER check-in: an earlier clock time means
            // the shift ran past midnight (e.g. 20:34 → 01:48 = 5h 14m).
            if ($end->lt($start)) {
                $end->addDay();
            }
            $minutes = (int) $start->diffInMinutes($end);
        }

        if (in_array($status, ['present', 'late', 'half_day'], true) && $in) {
            // The employee's shift (or the default config) decides late / half day.
            $startAt = $shift ? substr((string) $shift->start_time, 0, 5) : config('hrm.office_start');
            $grace = $shift?->grace_minutes ?? config('hrm.late_grace_minutes');
            $halfDay = $shift?->half_day_minutes ?? config('hrm.half_day_minutes');
            $start = Carbon::createFromFormat('H:i', $startAt)->addMinutes($grace);
            $checkIn = Carbon::createFromFormat('H:i', substr($in, 0, 5));
            // Night shifts: a check-in long before the start belongs to the next day's shift.
            $diff = $checkIn->diffInMinutes($start, false); // minutes until (start+grace)
            $status = $diff < 0 && $diff > -12 * 60 ? 'late' : 'present';
            if ($out && $minutes < $halfDay) {
                $status = 'half_day';
            }
        }

        return ['check_in' => $in, 'check_out' => $out, 'status' => $status, 'worked_minutes' => (int) $minutes];
    }
}
