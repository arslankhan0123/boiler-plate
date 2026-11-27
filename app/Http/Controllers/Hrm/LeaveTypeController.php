<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\LeaveRequest;
use App\Models\Hrm\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends HrmController
{
    public function index()
    {
        $this->need('hrm.manage_leave');

        return view('hrm.leave-types.index', [
            'types' => LeaveType::query()->orderBy('name')->get(),
            'editing' => request('edit') ? LeaveType::query()->find(request('edit')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $this->need('hrm.manage_leave');
        LeaveType::create($this->data($request));

        return back()->with('success', 'Leave type created.');
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        $this->need('hrm.manage_leave');
        $leaveType->update($this->data($request));

        return redirect()->route('hrm.leave-types.index')->with('success', 'Leave type updated.');
    }

    public function destroy(LeaveType $leaveType)
    {
        $this->need('hrm.manage_leave');
        if (LeaveRequest::query()->where('leave_type_id', $leaveType->id)->exists()) {
            $leaveType->update(['is_active' => false]);

            return back()->with('warning', 'This type has leave history, so it was deactivated instead of deleted.');
        }
        $leaveType->delete();

        return back()->with('success', 'Leave type deleted.');
    }

    private function data(Request $request): array
    {
        $d = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20'],
            'days_per_year' => ['required', 'numeric', 'min:0', 'max:366'],
            'max_carry_forward' => ['nullable', 'numeric', 'min:0', 'max:366'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        return $d + [
            'is_paid' => $request->boolean('is_paid'),
            'carry_forward' => $request->boolean('carry_forward'),
            'is_active' => $request->boolean('is_active', true),
            'max_carry_forward' => $d['max_carry_forward'] ?? 0,
        ];
    }
}
