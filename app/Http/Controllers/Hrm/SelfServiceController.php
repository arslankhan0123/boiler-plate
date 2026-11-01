<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Attendance;
use App\Models\Hrm\LeaveRequest;
use App\Models\Hrm\LeaveType;
use App\Models\Hrm\Payslip;
use App\Support\Hrm\Hrm;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Setup\Models\Designation;
use Modules\Setup\Models\Employee;

/** "My HR" — an employee only ever sees their own records here. */
class SelfServiceController extends HrmController
{
    private function me(): Employee
    {
        $this->need('hrm.self_service');

        return Hrm::meOrFail();
    }

    public function profile()
    {
        $me = $this->me()->load(['department', 'designation.parent', 'documents']);
        $manager = $me->designation?->parent_id
            ? Employee::query()->where('is_active', true)->where('designation_id', $me->designation->parent_id)->first() : null;

        return view('hrm.me.profile', [
            'employee' => $me,
            'manager' => $manager,
            'team' => Employee::query()->where('is_active', true)
                ->whereIn('designation_id', Designation::query()->where('parent_id', $me->designation_id)->pluck('id'))->get(),
            'balances' => Hrm::balances($me, now()->year),
            'today' => Attendance::query()->where('employee_id', $me->id)->whereDate('date', today())->first(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $me = $this->me();
        // Employees may only update their own contact details.
        $me->update($request->validate([
            'phone' => ['nullable', 'string', 'max:32'],
            'street' => ['nullable', 'string', 'max:190'],
            'city' => ['nullable', 'string', 'max:100'],
            'blood_group' => ['nullable', 'string', 'max:8'],
            'marital_status' => ['nullable', 'in:married,unmarried,divorced'],
        ]));

        return back()->with('success', 'Your details were updated.');
    }

    public function attendance(Request $request)
    {
        $me = $this->me();
        $month = Carbon::createFromFormat('Y-m', $request->input('month', now()->format('Y-m')))->startOfMonth();
        $records = Attendance::query()->where('employee_id', $me->id)
            ->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])->orderBy('date')->get();

        return view('hrm.me.attendance', [
            'employee' => $me,
            'month' => $month,
            'records' => $records,
            'today' => Attendance::query()->where('employee_id', $me->id)->whereDate('date', today())->first(),
            'summary' => collect(Attendance::STATUSES)->map(fn ($s, $k) => $records->where('status', $k)->count()),
        ]);
    }

    public function checkIn()
    {
        $me = $this->me();
        $today = Attendance::query()->firstOrNew(['employee_id' => $me->id, 'date' => today()->toDateString()]);
        abort_if($today->exists && $today->check_in, 422, 'You have already checked in today.');
        abort_if($today->status === 'on_leave', 422, 'You are on approved leave today.');

        $today->fill(AttendanceController::compute(now()->format('H:i'), null) + ['marked_by' => auth()->id()])->save();

        return back()->with('success', 'Checked in at '.now()->format('h:i A').'. Have a great day!');
    }

    public function checkOut()
    {
        $me = $this->me();
        $today = Attendance::query()->where('employee_id', $me->id)->whereDate('date', today())->first();
        abort_unless($today && $today->check_in, 422, 'Please check in first.');
        abort_if((bool) $today->check_out, 422, 'You have already checked out today.');

        $today->fill(AttendanceController::compute(substr((string) $today->check_in, 0, 5), now()->format('H:i')))->save();

        return back()->with('success', 'Checked out at '.now()->format('h:i A').'. Worked '.$today->workedLabel().'.');
    }

    public function leaves(Request $request)
    {
        $me = $this->me();
        $year = (int) $request->input('year', now()->year);

        return view('hrm.me.leaves', [
            'employee' => $me,
            'year' => $year,
            'balances' => Hrm::balances($me, $year),
            'requests' => LeaveRequest::query()->with('type')->where('employee_id', $me->id)->whereYear('start_date', $year)->latest()->get(),
            'types' => LeaveType::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function applyLeave(Request $request)
    {
        $me = $this->me();
        LeaveRequestController::createRequest($request, $me);

        return back()->with('success', 'Leave request submitted for approval.');
    }

    public function cancelLeave(LeaveRequest $leave)
    {
        $me = $this->me();
        abort_unless($leave->employee_id === $me->id, 404);
        abort_unless($leave->status === 'pending' || ($leave->status === 'approved' && $leave->start_date->isFuture()), 422,
            'Only pending or upcoming leave can be cancelled.');
        $leave->update(['status' => 'cancelled']);
        LeaveRequestController::syncAttendance($leave);

        return back()->with('success', 'Leave request cancelled.');
    }

    /* ───────────── My Interviews ───────────── */

    public function interviews(Request $request)
    {
        $me = $this->me();
        $tab = $request->input('tab', 'upcoming');

        $base = \App\Models\Hrm\JobApplication::query()->with('job.department')->where('interviewer_id', $me->id);

        return view('hrm.me.interviews.index', [
            'tab' => $tab,
            'interviews' => (clone $base)
                ->when($tab === 'upcoming', fn ($q) => $q->whereNull('feedback_at')->orderBy('interview_at'))
                ->when($tab === 'done', fn ($q) => $q->whereNotNull('feedback_at')->latest('feedback_at'))
                ->get(),
            'counts' => ['upcoming' => (clone $base)->whereNull('feedback_at')->count(), 'done' => (clone $base)->whereNotNull('feedback_at')->count()],
        ]);
    }

    public function interview(\App\Models\Hrm\JobApplication $application)
    {
        $me = $this->me();
        abort_unless($application->interviewer_id === $me->id, 404);

        return view('hrm.me.interviews.show', ['application' => $application->load('job.department')]);
    }

    public function interviewFeedback(Request $request, \App\Models\Hrm\JobApplication $application)
    {
        $me = $this->me();
        abort_unless($application->interviewer_id === $me->id, 404);

        $data = $request->validate([
            'interview_result' => ['required', 'in:'.implode(',', array_keys(\App\Models\Hrm\JobApplication::RESULTS))],
            'interview_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'interview_feedback' => ['required', 'string', 'max:5000'],
        ]);
        $application->update($data + ['feedback_at' => now()]);

        return redirect()->route('hrm.me.interviews')->with('success', "Feedback for {$application->name} sent to HR.");
    }

    public function payslips()
    {
        $me = $this->me();

        return view('hrm.me.payslips', [
            'employee' => $me,
            'payslips' => Payslip::query()->with('run')->where('employee_id', $me->id)
                ->whereHas('run', fn ($q) => $q->whereIn('status', ['finalized', 'paid']))->latest()->get(),
        ]);
    }
}
