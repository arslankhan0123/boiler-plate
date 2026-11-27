<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounting;

use App\Support\Accounting\Ledger;

/** Accounts Receivable: outstanding balances on receivable control accounts. */
class ReceivableController extends AccountingController
{
    public function index()
    {
        $this->need('accounting.view');
        $data = Ledger::partyBalances('receivable');

        return view('accounting.ar_ap.index', [
            'title' => 'Accounts Receivable', 'side' => 'receivable', 'icon' => 'cash-coin',
            'rows' => $this->withNames($data['rows']), 'total' => $data['total'],
        ]);
    }

    private function withNames(array $rows): array
    {
        foreach ($rows as &$r) {
            $r['name'] = $this->partyName($r['party_type'], $r['party_id']);
        }

        return $rows;
    }

    protected function partyName(?string $type, $id): string
    {
        if (! $type || ! $id) {
            return 'Unassigned';
        }
        $model = $type === 'customer' ? \App\Models\Retail\Customer::class : \App\Models\Retail\Supplier::class;

        return optional($model::find($id))->company_name ?? ($type.' #'.$id);
    }
}
