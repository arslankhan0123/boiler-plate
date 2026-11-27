<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\JournalLine;
use App\Support\Accounting\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Bank reconciliation: tick off posted bank lines against the statement. */
class ReconciliationController extends AccountingController
{
    public function index(Request $request)
    {
        $this->need('accounting.reconcile');
        $banks = Account::query()->where('is_bank', true)->orderBy('code')->get();
        $selId = (int) ($request->account_id ?: $banks->first()->id ?? 0);
        $selected = $banks->firstWhere('id', $selId);

        $lines = collect();
        $clearedBalance = 0.0;
        if ($selected) {
            $lines = DB::connection('tenant')->table('acc_journal_lines as l')
                ->join('acc_journals as j', 'j.id', '=', 'l.journal_id')
                ->where('j.status', 'posted')->where('l.account_id', $selected->id)
                ->orderBy('j.date')->orderBy('l.id')
                ->get(['l.id', 'j.date', 'j.reference', 'l.debit', 'l.credit', 'l.memo', 'l.reconciled']);
            $clearedBalance = $selected->opening_balance + $selected->sign() * $lines->where('reconciled', true)->sum(fn ($l) => (float) $l->debit - (float) $l->credit);
        }

        return view('accounting.reconciliation.index', [
            'banks' => $banks, 'selected' => $selected, 'lines' => $lines,
            'clearedBalance' => round($clearedBalance, 2),
            'bookBalance' => $selected ? Ledger::balance($selected) : 0.0,
        ]);
    }

    public function toggle(JournalLine $line)
    {
        $this->need('accounting.reconcile');
        $line->update(['reconciled' => ! $line->reconciled, 'reconciled_at' => $line->reconciled ? null : now()]);

        return back()->with('success', $line->reconciled ? 'Marked cleared.' : 'Unmarked.');
    }
}
