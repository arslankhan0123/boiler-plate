<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Support\Accounting\Ledger;

/** Accounts Payable: outstanding balances on payable control accounts. */
class PayableController extends ReceivableController
{
    public function index()
    {
        $this->need('accounting.view');
        $data = Ledger::partyBalances('payable');
        $rows = $data['rows'];
        foreach ($rows as &$r) {
            $r['name'] = $this->partyName($r['party_type'], $r['party_id']);
        }

        return view('accounting.ar_ap.index', [
            'title' => 'Accounts Payable', 'side' => 'payable', 'icon' => 'credit-card',
            'rows' => $rows, 'total' => $data['total'],
        ]);
    }
}
