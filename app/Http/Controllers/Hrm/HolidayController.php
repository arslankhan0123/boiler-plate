<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hrm;

use App\Models\Hrm\Holiday;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HolidayController extends HrmController
{
    public function index()
    {
        $this->need('hrm.manage_leave');
        $year = (int) request('year', now()->year);

        return view('hrm.holidays.index', [
            'year' => $year,
            'holidays' => Holiday::query()->whereYear('date', $year)->orderBy('date')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->need('hrm.manage_leave');
        Holiday::create($request->validate([
            'name' => ['required', 'string', 'max:150'],
            'date' => ['required', 'date', Rule::unique((new Holiday())->getTable(), 'date')],
        ]));

        return back()->with('success', 'Holiday added.');
    }

    public function destroy(Holiday $holiday)
    {
        $this->need('hrm.manage_leave');
        $holiday->delete();

        return back()->with('success', 'Holiday removed.');
    }
}
