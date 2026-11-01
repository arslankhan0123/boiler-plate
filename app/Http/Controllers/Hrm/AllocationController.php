<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\LeaveAllocation;
use App\Models\Hrm\LeaveType;
use App\Support\Hrm\Hrm;
use Illuminate\Http\Request;
use Modules\Setup\Models\Employee;

/** Yearly leave allocation per employee and leave type. */
class AllocationController extends HrmController
{
    public function index(Request $request)
    {
        $this->need('hrm.manage_leave');
        $year = (int) $request->input('year', now()->year);

        $types = LeaveType::query()->where('is_active', true)->where('is_paid', true)->orderBy('name')->get();
        $employees = Employee::query()->with('department')->where('is_active', true)
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')->paginate(25)->withQueryString();
        $allocations = LeaveAllocation::query()->where('year', $year)->whereIn('employee_id', $employees->pluck('id'))
            ->get()->groupBy('employee_id')->map->keyBy('leave_type_id');

        return view('hrm.allocations.index', compact('year', 'types', 'employees', 'allocations') + [
            'allocated' => LeaveAllocation::query()->where('year', $year)->exists(),
        ]);
    }

    public function allocate(Request $request)
    {
        $this->need('hrm.manage_leave');
        $year = (int) $request->validate(['year' => ['required', 'integer', 'min:2000', 'max:2100']])['year'];
        $n = Hrm::allocateYear($year);

        return back()->with('success', "Leave allocated for {$year} ({$n} balances updated, carry-forward applied).");
    }

    public function update(Request $request, Employee $employee)
    {
        $this->need('hrm.manage_leave');
        $data = $request->validate([
            'year' => ['required', 'integer'],
            'alloc' => ['array'],
            'alloc.*' => ['nullable', 'numeric', 'min:0', 'max:366'],
        ]);

        foreach ($data['alloc'] ?? [] as $typeId => $days) {
            LeaveAllocation::query()->updateOrCreate(
                ['employee_id' => $employee->id, 'leave_type_id' => (int) $typeId, 'year' => $data['year']],
                ['allocated' => (float) $days],
            );
        }

        return back()->with('success', "Allocation updated for {$employee->name}.");
    }
}
