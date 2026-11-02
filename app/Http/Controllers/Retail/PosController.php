<?php

declare(strict_types=1);

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Inventory\ItemController;
use App\Models\Inventory\Attribute;
use App\Models\Inventory\Warehouse;
use App\Models\Retail\Customer;
use App\Models\Retail\Document;
use App\Models\Retail\Salesman;
use App\Models\Retail\VoidLog;
use App\Models\Settings\TaxRate;
use App\Support\Retail\DocumentService;
use App\Support\Retail\Ledger;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * POS: fast checkout with barcode scanning, hold / resume, multiple payments with change return,
 * coupons, quick customer, thermal receipt, edit a POS invoice and a void log of removed items.
 */
class PosController extends RetailController
{
    public function index(Request $request)
    {
        $this->need('pos.sell');
        $editing = null;
        if ($request->filled('edit')) {
            $this->need('pos.edit');
            $editing = Document::query()->with('items.item.tax', 'items.item.variant', 'items.item.unit', 'items.item.stocks', 'customer', 'payments')
                ->where('type', 'invoice')->findOrFail($request->edit);
        }
        $walkIn = Customer::walkIn();

        return view('retail.pos.index', [
            'warehouses' => Warehouse::options(), 'defaultWarehouse' => Warehouse::default()->id,
            'categories' => Attribute::options('category'), 'brands' => Attribute::options('brand'),
            'salesmen' => Salesman::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'taxes' => TaxRate::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'rate']),
            'methods' => self::methods(true),
            'walkIn' => ['id' => $walkIn->id, 'name' => $walkIn->company_name, 'due' => 0, 'advance' => 0, 'price_level_type' => null, 'price_level' => 0, 'credit_limit' => 0, 'is_default' => true],
            'editing' => $editing ? $this->docJson($editing) : null,
            'held' => Document::query()->where('type', 'hold')->count(),
            'canDiscount' => $this->can('sales.discount') || $this->can('pos.discount'),
            'canHold' => $this->can('pos.hold'), 'canCustomer' => $this->can('sales.customers'),
            'roundOff' => Settings::enabled('round_off'),
            'today' => Document::query()->where('type', 'invoice')->where('is_pos', true)->whereDate('date', today())->where('created_by', auth()->id())
                ->selectRaw('COUNT(*) n, SUM(grand_total) total')->first(),
        ]);
    }

    /** Checkout. JSON in, JSON out (receipt URL). */
    public function store(Request $request)
    {
        $this->need('pos.sell');
        $data = $this->validated($request);
        $editId = $request->integer('edit_id') ?: null;
        if ($editId) {
            $this->need('pos.edit');
        }

        $doc = DB::transaction(function () use ($data, $editId, $request) {
            $doc = $editId ? Document::query()->where('type', 'invoice')->findOrFail($editId) : new Document(['is_pos' => true]);
            if ($editId) {
                $doc->payments()->delete(); // POS edit re-takes the payments from the screen
            }
            $grandPreview = $this->previewGrand($data['header'], $data['items'], $editId);
            $tendered = array_sum(array_map(fn ($p) => (float) $p['amount'], $data['payments']));
            $change = max(0, round($tendered - $grandPreview, 4));
            // Keep the payment rows at the bill amount; the extra is change returned.
            $payments = $data['payments'];
            if ($change > 0) {
                for ($i = count($payments) - 1; $i >= 0 && $change > 0; $i--) {
                    $cut = min((float) $payments[$i]['amount'], $change);
                    $payments[$i]['amount'] = (float) $payments[$i]['amount'] - $cut;
                }
            }
            $doc = DocumentService::save($doc, 'invoice', $data['header'] + ['is_pos' => true], $data['items'], $payments, $change);
            if ($request->filled('hold_id')) {
                $hold = Document::query()->where('type', 'hold')->find($request->integer('hold_id'));
                $hold?->forceDelete();
            }

            return $doc;
        });

        return response()->json([
            'ok' => true, 'id' => $doc->id, 'number' => $doc->number, 'grand_total' => $doc->grand_total, 'paid' => $doc->paid_amount,
            'change' => (float) $doc->payments()->sum('change_return'),
            'receipt' => route('sales.invoices.print', [$doc, 'format' => 'thermal', 'auto' => 1]),
            'a4' => route('sales.invoices.print', [$doc, 'format' => 'a4']),
            'held' => Document::query()->where('type', 'hold')->count(),
        ]);
    }

    public function hold(Request $request)
    {
        $this->need('pos.hold');
        $data = $this->validated($request, holding: true);
        $hold = $request->filled('hold_id') ? Document::query()->where('type', 'hold')->find($request->integer('hold_id')) : null;
        $doc = DocumentService::save($hold ?? new Document(['is_pos' => true]), 'hold', $data['header'] + ['status' => 'held', 'is_pos' => true, 'reference_no' => $request->input('hold_ref')], $data['items']);

        return response()->json(['ok' => true, 'id' => $doc->id, 'number' => $doc->number, 'held' => Document::query()->where('type', 'hold')->count()]);
    }

    public function holds()
    {
        $this->need('pos.hold');

        return response()->json(Document::query()->with('customer')->withCount('items')->where('type', 'hold')->latest()->get()
            ->map(fn ($d) => ['id' => $d->id, 'number' => $d->number, 'ref' => $d->reference_no, 'customer' => $d->customer->company_name ?? 'Walk-in',
                'items' => $d->items_count, 'total' => $d->grand_total, 'time' => $d->created_at?->format('d M, h:i A')]));
    }

    public function loadHold(Document $document)
    {
        $this->need('pos.hold');
        abort_unless($document->type === 'hold', 404);

        return response()->json($this->docJson($document->load('items.item.tax', 'items.item.variant', 'items.item.unit', 'items.item.stocks', 'customer')));
    }

    public function deleteHold(Document $document)
    {
        $this->need('pos.hold');
        abort_unless($document->type === 'hold', 404);
        $document->forceDelete();

        return response()->json(['ok' => true, 'held' => Document::query()->where('type', 'hold')->count()]);
    }

    /** Log items removed from the cart (void log). */
    public function void(Request $request)
    {
        $this->need('pos.sell');
        $data = $request->validate([
            'item_id' => ['nullable', 'integer'], 'item_name' => ['required', 'string', 'max:255'], 'qty' => ['required', 'numeric'],
            'price' => ['required', 'numeric'], 'customer_id' => ['nullable', 'integer'], 'reason' => ['nullable', 'string', 'max:255'],
        ]);
        VoidLog::create($data + ['user_id' => auth()->id()]);

        return response()->json(['ok' => true]);
    }

    private function validated(Request $request, bool $holding = false): array
    {
        $v = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:inv_warehouses,id'],
            'salesman_id' => ['nullable', 'integer', 'exists:rt_salesmen,id'],
            'other_charges_input' => ['nullable', 'numeric', 'min:0'],
            'other_charges_tax_id' => ['nullable', 'integer'],
            'discount_input' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer', 'exists:inv_items,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', 'in:percentage,fixed'],
            'items.*.discount_input' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_id' => ['nullable', 'integer'],
            'items.*.tax_type' => ['nullable', 'in:exclusive,inclusive'],
            'payments' => [$holding ? 'nullable' : 'present', 'array'],
            'payments.*.method' => ['required', 'string', 'max:50'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.note' => ['nullable', 'string', 'max:255'],
            'payments.*.cheque_no' => ['nullable', 'string', 'max:50'],
        ]);
        $customerId = $v['customer_id'] ?? Customer::walkIn()->id;
        $items = $v['items'];
        $header = [
            'customer_id' => $customerId, 'warehouse_id' => $v['warehouse_id'], 'salesman_id' => $v['salesman_id'] ?? null,
            'date' => today()->toDateString(), 'status' => 'final',
            'other_charges_input' => (float) ($v['other_charges_input'] ?? 0), 'other_charges_tax_id' => $v['other_charges_tax_id'] ?? null,
            'discount_input' => (float) ($v['discount_input'] ?? 0), 'discount_type' => $v['discount_type'] ?? 'percentage',
            'coupon_code' => $v['coupon_code'] ?? null, 'note' => $v['note'] ?? null,
        ];
        // Without discount permission, prices/discounts come from the item.
        if (! $this->can('sales.discount') && ! $this->can('pos.discount')) {
            $map = \App\Models\Inventory\Item::query()->whereIn('id', array_column($items, 'item_id'))->get()->keyBy('id');
            $customer = Customer::query()->find($customerId);
            foreach ($items as &$r) {
                $it = $map[$r['item_id']];
                $r['unit_price'] = $customer ? $customer->priceFor($it->sales_price) : $it->sales_price;
                $r['discount_type'] = $it->discount_type;
                $r['discount_input'] = $it->discount;
            }
            unset($r);
            $header['discount_input'] = 0;
        }
        // Walk-in customers must pay in full (no credit).
        if (! $holding && ($c = Customer::query()->find($customerId)) && $c->is_default) {
            $calc = DocumentService::calcDoc(array_map(fn ($r) => DocumentService::calcLine($r + ['tax_rate' => (float) TaxRate::query()->whereKey($r['tax_id'] ?? 0)->value('rate')]), $items), $header);
            $tendered = array_sum(array_map(fn ($p) => (float) $p['amount'], $v['payments'] ?? []));
            $couponAmt = ! empty($header['coupon_code']) ? DocumentService::coupon($header['coupon_code'], $customerId, $calc['subtotal'])['amount'] : 0;
            if ($tendered + 0.004 < $calc['grand_total'] - $couponAmt) {
                throw \Illuminate\Validation\ValidationException::withMessages(['payments' => 'Walk-in customers must pay the full amount. Choose a customer to sell on credit.']);
            }
        }

        return ['header' => $header, 'items' => $items, 'payments' => array_values(array_filter($v['payments'] ?? [], fn ($p) => (float) $p['amount'] > 0))];
    }

    /** Grand total the server will save (incl. coupon), used to split tendered cash into paid + change. */
    private function previewGrand(array $header, array $items, ?int $docId = null): float
    {
        $lines = array_map(fn ($r) => DocumentService::calcLine($r + ['tax_rate' => (float) TaxRate::query()->whereKey($r['tax_id'] ?? 0)->value('rate')]), $items);
        $sub = array_sum(array_column($lines, 'total'));
        $coupon = ! empty($header['coupon_code']) ? DocumentService::coupon($header['coupon_code'], $header['customer_id'] ?? null, $sub, $docId)['amount'] : 0;

        return DocumentService::calcDoc($lines, $header, $coupon)['grand_total'];
    }

    private function docJson(Document $d): array
    {
        $c = $d->customer;

        return [
            'id' => $d->id, 'type' => $d->type, 'number' => $d->number, 'ref' => $d->reference_no, 'warehouse_id' => $d->warehouse_id, 'salesman_id' => $d->salesman_id,
            'customer' => $c ? ['id' => $c->id, 'name' => $c->company_name, 'mobile' => $c->mobile, 'due' => Ledger::customerDue($c->id, $d->type === 'invoice' ? $d->id : null),
                'advance' => Ledger::customerAdvance($c->id), 'credit_limit' => $c->credit_limit, 'price_level_type' => $c->price_level_type, 'price_level' => $c->price_level, 'is_default' => $c->is_default] : null,
            'other_charges_input' => $d->other_charges_input, 'other_charges_tax_id' => $d->other_charges_tax_id,
            'discount_input' => $d->discount_input, 'discount_type' => $d->discount_type, 'coupon_code' => $d->coupon_code, 'note' => $d->note,
            'payments' => $d->type === 'invoice' ? $d->payments->map(fn ($p) => ['method' => $p->method, 'amount' => $p->amount])->values() : [],
            'lines' => $d->items->map(fn ($l) => ($l->item ? ItemController::json($l->item, $d->warehouse_id) : []) + [
                'qty' => $l->qty, 'unit_price' => $l->unit_price, 'discount_type' => $l->discount_type, 'discount_input' => $l->discount_input,
                'tax_id' => $l->tax_id, 'tax_type' => $l->tax_type,
            ])->values(),
        ];
    }
}
