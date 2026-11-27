<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Support\Accounting\Ledger;
use Illuminate\Http\Request;

/** Financial statements: trial balance, profit & loss, balance sheet. */
class StatementController extends AccountingController
{
    public function trialBalance(Request $request)
    {
        $this->need('accounting.reports');

        return view('accounting.statements.trial-balance', ['tb' => Ledger::trialBalance($request->to), 'to' => $request->to]);
    }

    public function profitLoss(Request $request)
    {
        $this->need('accounting.reports');
        $from = $request->from ?: now()->startOfYear()->toDateString();
        $to = $request->to ?: now()->toDateString();

        return view('accounting.statements.profit-loss', ['pl' => Ledger::profitLoss($from, $to), 'from' => $from, 'to' => $to]);
    }

    public function balanceSheet(Request $request)
    {
        $this->need('accounting.reports');
        $to = $request->to ?: now()->toDateString();

        return view('accounting.statements.balance-sheet', ['bs' => Ledger::balanceSheet($to), 'to' => $to]);
    }
}
