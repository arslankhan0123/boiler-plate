<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Account;
use App\Support\Accounting\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** General Ledger: posted movements of one account with a running balance. */
class LedgerController extends AccountingController
{
    public function index(Request $request)
    {
        $this->need('accounting.view');
        $accounts = Account::query()->where('is_group', false)->orderBy('code')->get();
        $accountId = (int) ($request->account_id ?: $accounts->first()->id ?? 0);
        $account = $accounts->firstWhere('id', $accountId);

        $rows = collect();
        $running = 0.0;
        if ($account) {
            $running = $account->opening_balance;
            $lines = DB::connection('tenant')->table('acc_journal_lines as l')
                ->join('acc_journals as j', 'j.id', '=', 'l.journal_id')
                ->where('j.status', 'posted')->where('l.account_id', $account->id)
                ->when($request->from, fn ($q, $v) => $q->whereDate('j.date', '>=', $v))
                ->when($request->to, fn ($q, $v) => $q->whereDate('j.date', '<=', $v))
                ->orderBy('j.date')->orderBy('l.id')
                ->get(['j.date', 'j.reference', 'j.id as jid', 'l.debit', 'l.credit', 'l.memo']);
            foreach ($lines as $ln) {
                $running += $account->sign() * ((float) $ln->debit - (float) $ln->credit);
                $rows->push(['row' => $ln, 'balance' => round($running, 2)]);
            }
        }

        return view('accounting.ledger.index', [
            'accounts' => $accounts->mapWithKeys(fn ($a) => [$a->id => $a->code.' — '.$a->name])->all(),
            'account' => $account, 'rows' => $rows, 'closing' => round($running, 2),
        ]);
    }
}
