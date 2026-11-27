<?php

declare(strict_types=1);

namespace App\Support\Retail;

use App\Models\Retail\Customer;
use App\Models\Retail\Document;
use App\Models\Retail\Payment;
use App\Models\Retail\Supplier;
use Illuminate\Support\Collection;

/**
 * Balances: what a customer owes us (receivable) / what we owe a supplier (payable),
 * plus customer advance balance and full statements.
 */
class Ledger
{
    /** Customer receivable: opening due + unpaid invoices − unrefunded returns. */
    public static function customerDue(int $customerId, ?int $exceptDocId = null): float
    {
        $c = Customer::query()->withTrashed()->find($customerId);
        if (! $c) {
            return 0;
        }
        $openingPaid = (float) Payment::query()->where('party_type', 'customer')->where('party_id', $customerId)->where('kind', 'opening')->sum('amount');
        $docs = Document::query()->where('customer_id', $customerId)->whereIn('type', ['invoice', 'sale_return'])->where('status', 'final')
            ->when($exceptDocId, fn ($q) => $q->whereKeyNot($exceptDocId))
            ->selectRaw("SUM(CASE WHEN type = 'invoice' THEN grand_total - paid_amount ELSE 0 END) inv, SUM(CASE WHEN type = 'sale_return' THEN grand_total - paid_amount ELSE 0 END) ret")
            ->first();

        return round($c->opening_balance - $openingPaid + (float) $docs->inv - (float) $docs->ret, 2);
    }

    public static function customerOpeningDue(Customer $c): float
    {
        return round($c->opening_balance - (float) Payment::query()->where('party_type', 'customer')->where('party_id', $c->id)->where('kind', 'opening')->sum('amount'), 2);
    }

    /** Advance money the customer has with us (paid in advance − used on invoices). */
    public static function customerAdvance(int $customerId): float
    {
        $in = (float) Payment::query()->where('party_type', 'customer')->where('party_id', $customerId)->where('kind', 'advance')->sum('amount');
        $used = (float) Payment::query()->where('party_type', 'customer')->where('party_id', $customerId)->where('kind', 'payment')->where('method', 'Advance')->sum('amount');

        return round($in - $used, 2);
    }

    /** Supplier payable: opening due + unpaid purchases − unrefunded purchase returns. */
    public static function supplierDue(int $supplierId, ?int $exceptDocId = null): float
    {
        $s = Supplier::query()->withTrashed()->find($supplierId);
        if (! $s) {
            return 0;
        }
        $openingPaid = (float) Payment::query()->where('party_type', 'supplier')->where('party_id', $supplierId)->where('kind', 'opening')->sum('amount');
        $docs = Document::query()->where('supplier_id', $supplierId)->whereIn('type', ['purchase', 'purchase_return'])
            ->when($exceptDocId, fn ($q) => $q->whereKeyNot($exceptDocId))
            ->selectRaw("SUM(CASE WHEN type = 'purchase' THEN grand_total - paid_amount ELSE 0 END) pur, SUM(CASE WHEN type = 'purchase_return' THEN grand_total - paid_amount ELSE 0 END) ret")
            ->first();

        return round($s->opening_balance - $openingPaid + (float) $docs->pur - (float) $docs->ret, 2);
    }

    public static function supplierOpeningDue(Supplier $s): float
    {
        return round($s->opening_balance - (float) Payment::query()->where('party_type', 'supplier')->where('party_id', $s->id)->where('kind', 'opening')->sum('amount'), 2);
    }

    /** Dues for many parties at once (lists). @return array<int, float> */
    public static function dues(string $partyType, Collection $parties): array
    {
        $ids = $parties->pluck('id')->all();
        if (! $ids) {
            return [];
        }
        $col = $partyType === 'customer' ? 'customer_id' : 'supplier_id';
        [$main, $ret] = $partyType === 'customer' ? ['invoice', 'sale_return'] : ['purchase', 'purchase_return'];
        $docs = Document::query()->whereIn($col, $ids)->whereIn('type', [$main, $ret])
            ->when($partyType === 'customer', fn ($q) => $q->where('status', 'final'))
            ->groupBy($col)
            ->selectRaw("$col pid, SUM(CASE WHEN type = '$main' THEN grand_total - paid_amount ELSE -(grand_total - paid_amount) END) due")
            ->pluck('due', 'pid');
        $opening = Payment::query()->where('party_type', $partyType)->whereIn('party_id', $ids)->where('kind', 'opening')
            ->groupBy('party_id')->selectRaw('party_id, SUM(amount) s')->pluck('s', 'party_id');

        return $parties->mapWithKeys(fn ($p) => [$p->id => round($p->opening_balance - (float) ($opening[$p->id] ?? 0) + (float) ($docs[$p->id] ?? 0), 2)])->all();
    }

    /**
     * Statement rows (date, description, debit, credit, running balance) for a customer or supplier.
     * Customer: debit = they owe more (invoice), credit = they paid / returned. Supplier: the mirror.
     */
    public static function statement(string $partyType, int $partyId, ?string $from = null, ?string $to = null): array
    {
        $party = $partyType === 'customer' ? Customer::query()->withTrashed()->findOrFail($partyId) : Supplier::query()->withTrashed()->findOrFail($partyId);
        [$main, $ret] = $partyType === 'customer' ? ['invoice', 'sale_return'] : ['purchase', 'purchase_return'];
        $col = $partyType === 'customer' ? 'customer_id' : 'supplier_id';

        $rows = collect();
        foreach (Document::query()->where($col, $partyId)->whereIn('type', [$main, $ret])->when($partyType === 'customer', fn ($q) => $q->where('status', 'final'))->get() as $d) {
            $rows->push(['date' => $d->date->toDateString(), 'sort' => 1, 'ref' => $d->number, 'doc' => $d, 'desc' => Document::TYPES[$d->type]['one'],
                'debit' => $d->type === $main ? $d->grand_total : 0, 'credit' => $d->type === $ret ? $d->grand_total : 0]);
        }
        foreach (Payment::query()->with('document')->where('party_type', $partyType)->where('party_id', $partyId)->get() as $p) {
            if ($p->kind === 'advance') {
                continue; // advances are tracked separately
            }
            $isRefund = $p->document && in_array($p->document->type, [$ret], true);
            $rows->push(['date' => $p->date->toDateString(), 'sort' => 2, 'ref' => $p->code, 'doc' => $p->document,
                'desc' => ($isRefund ? 'Refund' : ($p->kind === 'opening' ? 'Opening balance payment' : 'Payment')).' · '.$p->method.($p->document ? ' · '.$p->document->number : ''),
                'debit' => $isRefund ? $p->amount : 0, 'credit' => $isRefund ? 0 : $p->amount]);
        }
        $rows = $rows->sortBy([['date', 'asc'], ['sort', 'asc']])->values();

        $balance = (float) $party->opening_balance;
        $opening = $balance;
        $out = [];
        foreach ($rows as $r) {
            if ($from && $r['date'] < $from) {
                $opening += $r['debit'] - $r['credit'];
                $balance = $opening;

                continue;
            }
            if ($to && $r['date'] > $to) {
                continue;
            }
            $balance += $r['debit'] - $r['credit'];
            $out[] = $r + ['balance' => round($balance, 2)];
        }

        return ['party' => $party, 'opening' => round($opening, 2), 'rows' => $out, 'closing' => round($balance, 2),
            'debit' => array_sum(array_column($out, 'debit')), 'credit' => array_sum(array_column($out, 'credit'))];
    }
}
