<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Setup\Models\Employee;

class ShiftController extends HrmController
{
    public function index()
    {
        $this->need('hrm.manage_shifts');
        $counts = Employee::query()->where('is_active', true)->whereNotNull('shift_id')
            ->select('shift_id', DB::raw('COUNT(*) c'))->groupBy('shift_id')->pluck('c', 'shift_id');

        return view('hrm.shifts.index', [
            'shifts' => Shift::query()->orderByDesc('is_default')->orderBy('start_time')->get(),
            'counts' => $counts,
            'unassigned' => Employee::query()->where('is_active', true)->whereNull('shift_id')->count(),
            'editing' => request('edit') ? Shift::query()->find(request('edit')) : null,
        ]);
    }

    public function store(Request $request)
    {
        $this->need('hrm.manage_shifts');
        $shift = Shift::create($this->data($request));
        $this->ensureSingleDefault($shift);

        return back()->with('success', 'Shift created.');
    }

    public function update(Request $request, Shift $shift)
    {
        $this->need('hrm.manage_shifts');
        $shift->update($this->data($request));
        $this->ensureSingleDefault($shift);

        return redirect()->route('hrm.shifts.index')->with('success', 'Shift updated.');
    }

    public function destroy(Shift $shift)
    {
        $this->need('hrm.manage_shifts');
        Employee::query()->where('shift_id', $shift->id)->update(['shift_id' => null]);
        $shift->delete();

        return back()->with('success', 'Shift deleted. Its employees now follow the default shift.');
    }

    private function ensureSingleDefault(Shift $shift): void
    {
        if ($shift->is_default) {
            Shift::query()->whereKeyNot($shift->id)->update(['is_default' => false]);
        }
    }

    private function data(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'grace_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'half_day_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
        ]) + ['is_default' => $request->boolean('is_default')];
    }
}
