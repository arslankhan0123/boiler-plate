<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Account;
use App\Models\Settings\TaxRate;
use App\Support\Accounting\Ledger;

/** Tax management: rates (from Settings) and tax-liability account balances. */
class TaxController extends AccountingController
{
    public function index()
    {
        $this->need('accounting.manage_tax');
        $taxAccounts = Account::query()->where('is_group', false)
            ->where(fn ($q) => $q->where('name', 'like', '%tax%')->orWhere('code', 'like', '21%'))
            ->orderBy('code')->get();
        $balances = [];
        foreach ($taxAccounts as $a) {
            $balances[$a->id] = Ledger::balance($a);
        }

        return view('accounting.tax.index', [
            'rates' => TaxRate::query()->orderByDesc('is_default')->orderBy('name')->get(),
            'taxAccounts' => $taxAccounts, 'balances' => $balances,
            'totalLiability' => array_sum($balances),
        ]);
    }
}
