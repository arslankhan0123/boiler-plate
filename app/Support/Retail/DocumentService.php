<?php

declare(strict_types=1);

namespace App\Support\Retail;

use App\Models\Inventory\Item;
use App\Models\Retail\Coupon;
use App\Models\Retail\Customer;
use App\Models\Retail\CustomerCoupon;
use App\Models\Retail\Document;
use App\Models\Retail\Payment;
use App\Models\Settings\TaxRate;
use App\Support\Inventory\StockService;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Totals, saving, stock, coupons, credit limit and payments for every retail document.
 * The JS on the forms mirrors calcLine()/calcDoc(); the server result is what gets saved.
 */
class DocumentService
{
    /** One line: discount on (qty × price), then tax exclusive (added) or inclusive (inside). */
    public static function calcLine(array $l): array
    {
        $qty = (float) ($l['qty'] ?? 0);
        $price = (float) ($l['unit_price'] ?? 0);
        $rate = (float) ($l['tax_rate'] ?? 0);
        $gross = $qty * $price;
        $dIn = (float) ($l['discount_input'] ?? 0);
        $disc = ($l['discount_type'] ?? 'percentage') === 'percentage' ? $gross * $dIn / 100 : $dIn;
        $disc = min(max($disc, 0), max($gross, 0));
        $base = $gross - $disc;
        if (($l['tax_type'] ?? 'exclusive') === 'inclusive') {
            $total = $base;
            $tax = $rate > 0 ? $total - $total / (1 + $rate / 100) : 0;
        } else {
            $tax = $base * $rate / 100;
            $total = $base + $tax;
        }

        return [
            'discount_amt' => round($disc, 4), 'tax_amt' => round($tax, 4), 'total' => round($total, 4),
            'unit_total' => $qty != 0.0 ? round($total / $qty, 4) : 0,
        ];
    }

    /** Document totals from calculated lines + header inputs. */
    public static function calcDoc(array $lines, array $h, float $couponAmt = 0): array
    {
        $subtotal = array_sum(array_column($lines, 'total'));
        $otherRate = ! empty($h['other_charges_tax_id']) ? (float) TaxRate::query()->whereKey($h['other_charges_tax_id'])->value('rate') : 0;
        $otherIn = (float) ($h['other_charges_input'] ?? 0);
        $other = $otherIn + $otherIn * $otherRate / 100;
        $dIn = (float) ($h['discount_input'] ?? 0);
        $disc = ($h['discount_type'] ?? 'percentage') === 'percentage' ? $subtotal * $dIn / 100 : $dIn;
        $disc = min(max($disc, 0), $subtotal + $other);
        $raw = $subtotal + $other - $disc - $couponAmt;
        $round = Settings::enabled('round_off') ? round($raw) - $raw : 0;

        return [
            'subtotal' => round($subtotal, 4), 'tax_total' => round(array_sum(array_column($lines, 'tax_amt')), 4),
            'item_discount_total' => round(array_sum(array_column($lines, 'discount_amt')), 4),
            'other_charges_amt' => round($other, 4), 'discount_amt' => round($disc, 4), 'coupon_amt' => round($couponAmt, 4),
            'round_off' => round($round, 4), 'grand_total' => round(max(0, $raw + $round), 4),
        ];
    }

    public static function nextNumber(string $type): string
    {
        $prefix = Settings::get('prefix_'.$type) ?: Document::TYPES[$type]['prefix'];
        $last = Document::query()->withTrashed()->where('type', $type)->where('number', 'like', $prefix.'%')->orderByDesc('id')->value('number');
        $n = $last ? ((int) preg_replace('/\D/', '', substr($last, strlen($prefix)))) + 1 : 1;
        do {
            $number = $prefix.str_pad((string) $n++, 4, '0', STR_PAD_LEFT);
        } while (Document::query()->withTrashed()->where('type', $type)->where('number', $number)->exists());

        return $number;
    }

    /**
     * Find a coupon by code: a general discount coupon, or a customer coupon issued to this customer.
     * @return array{coupon_id:int, customer_coupon_id:?int, code:string, amount:float, label:string}
     */
    public static function coupon(string $code, ?int $customerId, float $subtotal, ?int $docId = null): array
    {
        $code = trim($code);
        $cc = CustomerCoupon::query()->with('coupon')->where('code', $code)->first();
        if ($cc) {
            $c = $cc->coupon;
            if ((int) $cc->customer_id !== (int) $customerId) {
                throw ValidationException::withMessages(['coupon_code' => 'This coupon belongs to another customer.']);
            }
            if (! $cc->is_active || ($cc->used_document_id && (int) $cc->used_document_id !== (int) $docId)) {
                throw ValidationException::withMessages(['coupon_code' => 'This coupon has already been used.']);
            }
            if ($cc->expire_date && $cc->expire_date->lt(today())) {
                throw ValidationException::withMessages(['coupon_code' => 'This coupon has expired.']);
            }
        } else {
            $c = Coupon::query()->where('code', $code)->first();
            if (! $c) {
                throw ValidationException::withMessages(['coupon_code' => 'Coupon code not found.']);
            }
        }
        if (! $c->is_active || $c->isExpired()) {
            throw ValidationException::withMessages(['coupon_code' => 'This coupon is inactive or expired.']);
        }
        if ($c->min_amount > 0 && $subtotal < $c->min_amount) {
            throw ValidationException::withMessages(['coupon_code' => 'Coupon needs a minimum bill of '.number_format($c->min_amount, 2).'.']);
        }
        $amount = $c->type === 'percentage' ? $subtotal * $c->value / 100 : min($c->value, $subtotal);

        return ['coupon_id' => $c->id, 'customer_coupon_id' => $cc?->id, 'code' => $code, 'amount' => round($amount, 4), 'label' => $c->name.' ('.$c->label().')'];
    }

    /**
     * Save a document with its lines (and optional new payments). Reverses old stock first,
     * then writes lines, stock movements, coupon usage, item price updates and payment status.
     *
     * @param  array  $header  validated header fields
     * @param  array  $rows    [{item_id, qty, unit_price, discount_type, discount_input, tax_id, tax_type, description, sales_price?, profit_margin?}]
     * @param  array  $payments [{method, amount, note, cheque_no, cheque_date}]
     */
    public static function save(Document $doc, string $type, array $header, array $rows, array $payments = [], float $changeReturn = 0): Document
    {
        $meta = Document::meta($type);
        $rows = array_values(array_filter($rows, fn ($r) => ! empty($r['item_id']) && (float) ($r['qty'] ?? 0) > 0));
        if (! $rows) {
            throw ValidationException::withMessages(['items' => 'Add at least one item.']);
        }

        return DB::transaction(function () use ($doc, $type, $meta, $header, $rows, $payments, $changeReturn) {
            $items = Item::query()->withTrashed()->with('tax')->whereIn('id', array_column($rows, 'item_id'))->get()->keyBy('id');
            $taxes = TaxRate::query()->pluck('rate', 'id');

            $lines = [];
            foreach ($rows as $r) {
                $item = $items[$r['item_id']] ?? null;
                if (! $item) {
                    continue;
                }
                $taxId = array_key_exists('tax_id', $r) ? ($r['tax_id'] ?: null) : $item->tax_id;
                $line = [
                    'item_id' => $item->id, 'description' => $r['description'] ?? null,
                    'qty' => (float) $r['qty'], 'unit_price' => (float) ($r['unit_price'] ?? 0),
                    'tax_id' => $taxId, 'tax_rate' => (float) ($taxes[$taxId] ?? 0), 'tax_type' => $r['tax_type'] ?? $item->tax_type,
                    'discount_type' => $r['discount_type'] ?? 'percentage', 'discount_input' => (float) ($r['discount_input'] ?? 0),
                    'purchase_price' => $meta['side'] === 'purchase' ? (float) ($r['unit_price'] ?? 0) : $item->purchase_price,
                    'seller_points' => $meta['side'] === 'sale' ? (float) $item->seller_points * (float) $r['qty'] : 0,
                    'sales_price' => isset($r['sales_price']) && $r['sales_price'] !== '' ? (float) $r['sales_price'] : null,
                    'profit_margin' => isset($r['profit_margin']) && $r['profit_margin'] !== '' ? (float) $r['profit_margin'] : null,
                ];
                $lines[] = $line + self::calcLine($line);
            }

            // Coupon (sales only)
            $couponAmt = 0;
            $coupon = null;
            if ($meta['side'] === 'sale' && ! empty($header['coupon_code'])) {
                $coupon = self::coupon($header['coupon_code'], $header['customer_id'] ?? null, array_sum(array_column($lines, 'total')), $doc->id);
                $couponAmt = $coupon['amount'];
            }
            $totals = self::calcDoc($lines, $header, $couponAmt);

            // Credit limit (final sales invoices on credit)
            if ($type === 'invoice' && ($header['status'] ?? 'final') === 'final' && ! empty($header['customer_id'])) {
                $customer = Customer::query()->find($header['customer_id']);
                $paidNow = array_sum(array_map(fn ($p) => (float) ($p['amount'] ?? 0), $payments)) + ($doc->exists ? (float) $doc->payments()->sum('amount') : 0);
                $newDue = max(0, $totals['grand_total'] - $paidNow);
                if ($customer && $customer->credit_limit > 0 && $newDue > 0) {
                    $existing = Ledger::customerDue($customer->id, $doc->id ?: null);
                    if ($existing + $newDue > $customer->credit_limit + 0.001) {
                        throw ValidationException::withMessages(['customer_id' => sprintf('Credit limit exceeded: limit %s, current due %s, this bill adds %s.',
                            number_format($customer->credit_limit, 2), number_format($existing, 2), number_format($newDue, 2))]);
                    }
                }
            }

            // Reverse what the old version did
            if ($doc->exists) {
                StockService::reverse($doc);
                $doc->items()->delete();
                CustomerCoupon::query()->where('used_document_id', $doc->id)->update(['used_document_id' => null, 'used_at' => null]);
            }

            $doc->fill(array_merge($header, $totals, [
                'type' => $type,
                'coupon_id' => $coupon['coupon_id'] ?? null, 'customer_coupon_id' => $coupon['customer_coupon_id'] ?? null, 'coupon_code' => $coupon['code'] ?? null,
                'created_by' => $doc->created_by ?? auth()->id(),
            ]));
            if (! $doc->exists) {
                $doc->number = $header['number'] ?? self::nextNumber($type);
                if ($type === 'invoice' && ! empty($header['customer_id'])) {
                    $doc->previous_due = Ledger::customerDue((int) $header['customer_id']);
                }
            }
            $doc->save();

            foreach ($lines as $line) {
                $doc->items()->create($line);
            }

            if (! empty($coupon['customer_coupon_id'])) {
                CustomerCoupon::query()->whereKey($coupon['customer_coupon_id'])->update(['used_document_id' => $doc->id, 'used_at' => now()]);
            }

            self::applyStock($doc);

            if ($type === 'purchase' && $doc->update_prices && $doc->status === 'received') {
                self::updateItemPrices($doc);
            }

            foreach ($payments as $p) {
                self::addPayment($doc, $p, $changeReturn);
                $changeReturn = 0; // change is recorded once
            }
            self::refreshPaid($doc);

            return $doc->fresh(['items']);
        });
    }

    public static function applyStock(Document $doc): void
    {
        if (! $doc->affectsStock()) {
            return;
        }
        $sign = Document::TYPES[$doc->type]['stock'];
        $doc->loadMissing('items');
        foreach ($doc->items as $l) {
            StockService::move((int) $l->item_id, (int) $doc->warehouse_id, $sign * $l->qty, $doc->type, $doc, $doc->number, $doc->date->toDateString());
        }
    }

    /** Purchase: copy cost and the new sales price / margin back to the items. */
    public static function updateItemPrices(Document $doc): void
    {
        foreach ($doc->items()->with('item')->get() as $l) {
            if (! $l->item) {
                continue;
            }
            $price = $l->tax_type === 'exclusive' ? $l->unit_price : ($l->tax_rate > 0 ? $l->unit_price / (1 + $l->tax_rate / 100) : $l->unit_price);
            $prices = \App\Http\Controllers\Inventory\ItemController::prices(round($price, 4), $l->tax_rate, $l->item->tax_type, $l->profit_margin ?? $l->item->profit_margin, $l->sales_price);
            Item::query()->whereKey($l->item_id)->update($prices);
        }
    }

    public static function addPayment(Document $doc, array $p, float $changeReturn = 0): ?Payment
    {
        $amount = round((float) ($p['amount'] ?? 0), 4);
        if ($amount <= 0) {
            return null;
        }
        $meta = $doc->info();
        $isReturn = in_array($doc->type, ['sale_return', 'purchase_return'], true);
        $direction = match (true) {
            $meta['side'] === 'sale' && ! $isReturn, $meta['side'] === 'purchase' && $isReturn => 'in',
            default => 'out',
        };
        $partyType = $meta['side'] === 'sale' ? 'customer' : 'supplier';
        $partyId = $meta['side'] === 'sale' ? $doc->customer_id : $doc->supplier_id;
        $method = $p['method'] ?? 'Cash';

        if ($method === 'Advance') {
            if (! $partyId || $partyType !== 'customer') {
                throw ValidationException::withMessages(['payments' => 'Advance can only be used for a customer.']);
            }
            $available = Ledger::customerAdvance((int) $partyId);
            if ($amount > $available + 0.001) {
                throw ValidationException::withMessages(['payments' => 'Advance balance is only '.number_format($available, 2).'.']);
            }
        }

        return Payment::create([
            'code' => self::paymentCode($direction),
            'direction' => $direction, 'kind' => $isReturn ? 'refund' : 'payment', 'party_type' => $partyType, 'party_id' => (int) $partyId,
            'document_id' => $doc->id, 'date' => $p['date'] ?? $doc->date->toDateString(), 'method' => $method, 'amount' => $amount,
            'change_return' => $changeReturn, 'cheque_no' => $p['cheque_no'] ?? null, 'cheque_date' => $p['cheque_date'] ?? null,
            'cheque_status' => ! empty($p['cheque_no']) ? 'pending' : null, 'note' => $p['note'] ?? null, 'created_by' => auth()->id(),
        ]);
    }

    public static function paymentCode(string $direction): string
    {
        $prefix = $direction === 'in' ? (Settings::get('prefix_receipt') ?: 'RV') : (Settings::get('prefix_voucher') ?: 'PV');
        $last = Payment::query()->where('code', 'like', $prefix.'%')->orderByDesc('id')->value('code');
        $n = $last ? ((int) preg_replace('/\D/', '', substr($last, strlen($prefix)))) + 1 : 1;

        return $prefix.str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }

    /** paid_amount + payment_status from the payment rows (bounced cheques don't count). */
    public static function refreshPaid(Document $doc): void
    {
        $paid = (float) $doc->payments()->where(fn ($q) => $q->whereNull('cheque_status')->orWhere('cheque_status', '!=', 'bounced'))->sum('amount');
        $status = $paid <= 0.004 ? 'unpaid' : ($paid + 0.004 >= $doc->grand_total ? 'paid' : 'partial');
        Document::query()->whereKey($doc->id)->update(['paid_amount' => round($paid, 4), 'payment_status' => $doc->grand_total <= 0 ? 'paid' : $status]);
        $doc->paid_amount = round($paid, 4);
        $doc->payment_status = $status;
    }

    public static function delete(Document $doc): void
    {
        DB::transaction(function () use ($doc) {
            StockService::reverse($doc);
            CustomerCoupon::query()->where('used_document_id', $doc->id)->update(['used_document_id' => null, 'used_at' => null]);
            $doc->payments()->delete();
            if ($doc->parent && in_array($doc->parent->type, ['quotation', 'delivery', 'order', 'rfq'], true) && $doc->parent->status === 'converted') {
                $doc->parent->update(['status' => array_key_first(Document::TYPES[$doc->parent->type]['statuses'])]);
            }
            $doc->delete();
        });
    }
}
