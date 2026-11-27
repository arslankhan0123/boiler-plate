<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Attendance;
use App\Models\Hrm\LeaveRequest;
use App\Models\Hrm\Review;
use App\Support\Hrm\Hrm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * "My Team" for managers: direct reports (from the org chart), their
 * attendance today, and approving their leave — no HR permission needed.
 */
class TeamController extends HrmController
{
    private function team()
    {
        $this->need('hrm.self_service');
        Hrm::meOrFail();
        $team = Hrm::team();
        abort_if($team->isEmpty(), 403, 'Nobody reports to you in the organization chart.');

        return $team;
    }

    public function index()
    {
        $team = $this->team();
        $ids = $team->pluck('id');

        return view('hrm.team.index', [
            'team' => $team,
            'today' => Attendance::query()->whereIn('employee_id', $ids)->whereDate('date', today())->get()->keyBy('employee_id'),
            'pending' => LeaveRequest::query()->with(['employee', 'type'])->whereIn('employee_id', $ids)->where('status', 'pending')->orderBy('start_date')->get(),
            'upcoming' => LeaveRequest::query()->with(['employee', 'type'])->whereIn('employee_id', $ids)->where('status', 'approved')
                ->whereDate('end_date', '>=', today())->orderBy('start_date')->limit(10)->get(),
            'reviews' => Review::query()->whereIn('employee_id', $ids)->latest()->get()->groupBy('employee_id'),
        ]);
    }

    public function decide(Request $request, LeaveRequest $leave, string $decision)
    {
        $team = $this->team();
        abort_unless($team->contains('id', $leave->employee_id), 404);
        abort_unless(in_array($decision, ['approve', 'reject'], true), 404);
        abort_unless($leave->status === 'pending', 422, 'This request was already handled.');

        DB::transaction(function () use ($leave, $decision, $request) {
            $leave->update([
                'status' => $decision === 'approve' ? 'approved' : 'rejected',
                'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'review_note' => $request->input('note'),
            ]);
            LeaveRequestController::syncAttendance($leave);
        });

        return back()->with('success', 'Leave '.($decision === 'approve' ? 'approved' : 'rejected')." for {$leave->employee->name}.");
    }
}
