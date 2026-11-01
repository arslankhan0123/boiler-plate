<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Attendance;
use App\Models\Hrm\LeaveRequest;
use App\Models\Hrm\LeaveType;
use App\Support\Hrm\Hrm;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Setup\Models\Employee;

/** HR side: all leave requests, approve / reject, apply on behalf. */
class LeaveRequestController extends HrmController
{
    public function index(Request $request)
    {
        $this->need('hrm.approve_leave');
        $status = $request->input('status', 'pending');

        return view('hrm.leaves.index', [
            'status' => $status,
            'requests' => LeaveRequest::query()->with(['employee.department', 'type'])
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->when($request->filled('employee'), fn ($q) => $q->where('employee_id', $request->employee))
                ->latest()->paginate(20)->withQueryString(),
            'counts' => LeaveRequest::query()->select('status', DB::raw('COUNT(*) c'))->groupBy('status')->pluck('c', 'status'),
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'types' => LeaveType::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /** HR applies on behalf of an employee (auto-approved). */
    public function store(Request $request)
    {
        $this->need('hrm.approve_leave');
        $employee = Employee::query()->findOrFail($request->input('employee_id'));
        $leave = self::createRequest($request, $employee);
        $this->decide($leave, 'approved', 'Added by HR');

        return back()->with('success', 'Leave recorded and approved.');
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        $this->need('hrm.approve_leave');
        $this->guardNotSelf($leave);
        abort_unless($leave->status === 'pending', 422, 'Only pending requests can be approved.');
        $this->decide($leave, 'approved', $request->input('note'));

        return back()->with('success', "Leave approved for {$leave->employee->name}.");
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $this->need('hrm.approve_leave');
        $this->guardNotSelf($leave);
        abort_unless(in_array($leave->status, ['pending', 'approved'], true), 422);
        $this->decide($leave, 'rejected', $request->input('note'));

        return back()->with('success', 'Leave rejected.');
    }

    private function guardNotSelf(LeaveRequest $leave): void
    {
        abort_if(Hrm::me()?->id === $leave->employee_id && ! auth()->user()->hasRole(\App\Support\Authorization\DefaultRoles::TENANT_ADMIN),
            403, 'You cannot approve your own leave.');
    }

    private function decide(LeaveRequest $leave, string $status, ?string $note): void
    {
        DB::transaction(function () use ($leave, $status, $note) {
            $leave->update(['status' => $status, 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'review_note' => $note]);
            self::syncAttendance($leave);
        });
    }

    /** Approved leave marks attendance "on_leave"; otherwise those marks are removed. */
    public static function syncAttendance(LeaveRequest $leave): void
    {
        $dates = Hrm::workingDates($leave->start_date, $leave->end_date);
        foreach ($dates as $date) {
            if ($leave->status === 'approved') {
                Attendance::query()->updateOrCreate(
                    ['employee_id' => $leave->employee_id, 'date' => $date->toDateString()],
                    ['status' => 'on_leave', 'note' => $leave->type?->name, 'marked_by' => auth()->id()],
                );
            } else {
                Attendance::query()->where('employee_id', $leave->employee_id)->whereDate('date', $date)->where('status', 'on_leave')->delete();
            }
        }
    }

    /** Shared by HR + self-service: validate dates, overlap and balance. */
    public static function createRequest(Request $request, Employee $employee): LeaveRequest
    {
        $data = $request->validate([
            'leave_type_id' => ['required', Rule::exists((new LeaveType())->getTable(), 'id')->where('is_active', true)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'half_day' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $days = (float) Hrm::workingDayCount($start, $end);
        if ($request->boolean('half_day') && $start->isSameDay($end)) {
            $days = 0.5;
        }
        if ($days <= 0) {
            throw ValidationException::withMessages(['start_date' => 'The selected dates are weekends or holidays.']);
        }
        if ($start->year !== $end->year) {
            throw ValidationException::withMessages(['end_date' => 'Please split leave that crosses into a new year.']);
        }

        $overlap = LeaveRequest::query()->where('employee_id', $employee->id)->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['start_date' => 'There is already a leave request for these dates.']);
        }

        $type = LeaveType::query()->find($data['leave_type_id']);
        if ($type->is_paid) {
            $balance = Hrm::balances($employee, $start->year)->firstWhere(fn ($b) => $b['type']->id === $type->id);
            if (($balance['remaining'] ?? 0) < $days) {
                throw ValidationException::withMessages(['leave_type_id' => "Not enough {$type->name} balance ({$balance['remaining']} days left, {$days} requested)."]);
            }
        }

        return LeaveRequest::create([
            'employee_id' => $employee->id, 'leave_type_id' => $type->id, 'start_date' => $start, 'end_date' => $end,
            'days' => $days, 'reason' => $data['reason'] ?? null, 'status' => 'pending',
        ]);
    }
}
