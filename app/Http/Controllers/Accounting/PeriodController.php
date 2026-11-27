<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Period;
use Illuminate\Http\Request;

/** Accounting periods: open/close to lock postings. */
class PeriodController extends AccountingController
{
    public function index()
    {
        $this->need('accounting.close_period');

        return view('accounting.periods.index', ['periods' => Period::query()->orderByDesc('start_date')->get()]);
    }

    public function store(Request $request)
    {
        $this->need('accounting.close_period');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);
        Period::create($data + ['status' => 'open', 'created_by' => auth()->id()]);

        return back()->with('success', 'Period added.');
    }

    public function close(Period $period)
    {
        $this->need('accounting.close_period');
        $period->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => auth()->id()]);

        return back()->with('success', 'Period closed. Postings in this range are now locked.');
    }

    public function reopen(Period $period)
    {
        $this->need('accounting.close_period');
        $period->update(['status' => 'open', 'closed_at' => null, 'closed_by' => null]);

        return back()->with('success', 'Period reopened.');
    }

    public function destroy(Period $period)
    {
        $this->need('accounting.close_period');
        $period->delete();

        return back()->with('success', 'Period deleted.');
    }
}
