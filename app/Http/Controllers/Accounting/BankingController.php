<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Account;
use App\Support\Accounting\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Bank & cash accounts with their transaction register. */
class BankingController extends AccountingController
{
    public function index(Request $request)
    {
        $this->need('accounting.view');
        $banks = Account::query()->where('is_bank', true)->orderBy('code')->get();
        $balances = [];
        foreach ($banks as $b) {
            $balances[$b->id] = Ledger::balance($b);
        }
        $selId = (int) ($request->account_id ?: $banks->first()->id ?? 0);
        $selected = $banks->firstWhere('id', $selId);
        $register = collect();
        if ($selected) {
            $register = DB::connection('tenant')->table('acc_journal_lines as l')
                ->join('acc_journals as j', 'j.id', '=', 'l.journal_id')
                ->where('j.status', 'posted')->where('l.account_id', $selected->id)
                ->orderByDesc('j.date')->orderByDesc('l.id')->limit(100)
                ->get(['j.date', 'j.reference', 'l.debit', 'l.credit', 'l.memo', 'l.reconciled']);
        }

        return view('accounting.banking.index', [
            'banks' => $banks, 'balances' => $balances, 'selected' => $selected, 'register' => $register,
            'totalCash' => array_sum($balances),
        ]);
    }
}
