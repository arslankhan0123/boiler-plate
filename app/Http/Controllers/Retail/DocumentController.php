<?php

declare(strict_types=1);

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Inventory\ItemController;
use App\Models\Inventory\Item;
use App\Models\Inventory\Warehouse;
use App\Models\Retail\Customer;
use App\Models\Retail\Document;
use App\Models\Retail\DocumentItem;
use App\Models\Retail\Payment;
use App\Models\Retail\Salesman;
use App\Models\Retail\Supplier;
use App\Models\Settings\TaxRate;
use App\Models\User;
use App\Support\Retail\DocumentService;
use App\Support\Retail\Ledger;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every retail document screen. The route passes {type} as a default:
 * invoice, quotation, delivery, sale_return, order, purchase, purchase_return.
 */
class DocumentController extends RetailController
{
    public function index(Request $request, string $type)
    {
        $meta = Document::meta($type);
        $this->need($meta['perm']);
        $partyCol = $meta['side'] === 'sale' ? 'customer_id' : 'supplier_id';

        $base = Document::query()->where('type', $type)
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('number', 'like', "%$s%")->orWhere('reference_no', 'like', "%$s%")
                ->orWhereHas($meta['side'] === 'sale' ? 'customer' : 'supplier', fn ($p) => $p->where('company_name', 'like', "%$s%")->orWhere('mobile', 'like', "%$s%"))))
            ->when($request->party, fn ($q, $v) => $q->where($partyCol, $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->payment, fn ($q, $v) => $q->where('payment_status', $v))
            ->when($request->warehouse, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($request->salesman, fn ($q, $v) => $q->where('salesman_id', $v))
            ->when($request->channel === 'pos', fn ($q) => $q->where('is_pos', true))
            ->when($request->channel === 'normal', fn ($q) => $q->where('is_pos', false))
            ->when($request->from, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($request->user, fn ($q, $v) => $q->where('created_by', $v));

        $docs = (clone $base)->with('customer', 'supplier', 'warehouse', 'salesman')->latest('date')->latest('id')->paginate(25)->withQueryString();

        return view('retail.documents.index', [
            'meta' => $meta, 'docs' => $docs,
            'sum' => (clone $base)->selectRaw('COUNT(*) n, SUM(grand_total) total, SUM(paid_amount) paid')->first(),
            'warehouses' => Warehouse::options(),
            'salesmen' => $meta['side'] === 'sale' ? Salesman::query()->orderBy('name')->pluck('name', 'id') : collect(),
            'party' => $request->party ? ($meta['side'] === 'sale' ? Customer::query()->withTrashed()->find($request->party) : Supplier::query()->withTrashed()->find($request->party)) : null,
            'users' => User::query()->whereIn('id', $docs->pluck('created_by')->filter())->pluck('name', 'id'),
            'can' => $this->perms($meta),
        ]);
    }

    public function create(Request $request, string $type)
    {
        $meta = Document::meta($type);
        $this->need($meta['perm_create']);
        $doc = new Document([
            'type' => $type, 'date' => today(), 'warehouse_id' => Warehouse::default()->id, 'status' => array_key_first($meta['statuses']),
            'discount_type' => 'percentage', 'terms' => $meta['side'] === 'sale' ? Settings::get('invoice_terms') : null,
            'due_date' => $type === 'quotation' ? today()->addDays(15) : null,
            'customer_id' => $meta['side'] === 'sale' && $type !== 'quotation' ? Customer::walkIn()->id : null,
        ]);
        $lines = collect();

        // Convert / return from another document
        if ($request->filled('from')) {
            $src = Document::query()->with('items.item.tax', 'items.item.variant', 'items.item.unit', 'items.item.stocks')->findOrFail($request->from);
            $doc->fill($src->only(['customer_id', 'supplier_id', 'warehouse_id', 'salesman_id', 'other_charges_input', 'other_charges_tax_id', 'discount_input', 'discount_type', 'note', 'terms', 'reference_no']));
            $doc->parent_id = $src->id;
            $returned = in_array($type, ['sale_return', 'purchase_return'], true) ? $this->returnedQty($src) : [];
            $lines = $src->items->map(function (DocumentItem $l) use ($returned, $type, $doc) {
                $qty = $l->qty - ($returned[$l->item_id] ?? 0);

                return $this->lineJson($l->item, $doc->warehouse_id) + [
                    'qty' => in_array($type, ['sale_return', 'purchase_return'], true) ? max(0, $qty) : $l->qty, 'max_qty' => in_array($type, ['sale_return', 'purchase_return'], true) ? max(0, $qty) : null,
                    'unit_price' => $l->unit_price, 'discount_type' => $l->discount_type, 'discount_input' => $l->discount_input,
                    'tax_id' => $l->tax_id, 'tax_type' => $l->tax_type, 'description' => $l->description,
                ];
            })->values();
        }

        return view('retail.documents.form', $this->formData($doc, $meta, $lines));
    }

    public function store(Request $request, string $type)
    {
        $meta = Document::meta($type);
        $this->need($meta['perm_create']);
        [$header, $rows, $payments] = $this->input($request, $meta);
        $doc = DocumentService::save(new Document(), $type, $header, $rows, $payments);
        $this->afterSave($doc);

        if ($request->boolean('print')) {
            return redirect()->route($meta['route'].'.print', [$doc, 'auto' => 1]);
        }

        return redirect()->route($meta['route'].'.show', $doc)->with('success', $meta['one'].' '.$doc->number.' saved.');
    }

    public function show(Document $document, string $type)
    {
        $meta = $this->guard($document, $type, 'perm');
        $document->load('items.item.unit', 'items.item.variant', 'payments', 'customer', 'supplier', 'warehouse', 'salesman', 'parent', 'children');

        return view('retail.documents.show', [
            'meta' => $meta, 'doc' => $document, 'methods' => self::methods($meta['side'] === 'sale' && $type === 'invoice'),
            'by' => User::query()->whereKey($document->created_by)->value('name'),
            'partyDue' => $meta['side'] === 'sale' ? ($document->customer_id ? Ledger::customerDue($document->customer_id) : 0) : ($document->supplier_id ? Ledger::supplierDue($document->supplier_id) : 0),
            'advance' => $document->customer_id ? Ledger::customerAdvance($document->customer_id) : 0,
            'can' => $this->perms($meta),
        ]);
    }

    public function edit(Document $document, string $type)
    {
        $meta = $this->guard($document, $type, 'perm_edit');
        if ($document->is_pos && $type === 'invoice') {
            return redirect()->route('pos', ['edit' => $document->id]);
        }
        $document->load('items.item.tax', 'items.item.variant', 'items.item.unit', 'items.item.stocks');
        $max = $document->parent_id && in_array($type, ['sale_return', 'purchase_return'], true) ? $this->returnedQty($document->parent, $document->id) : null;
        $parentQty = $max !== null ? $document->parent->items->groupBy('item_id')->map->sum('qty') : collect();
        $lines = $document->items->map(fn (DocumentItem $l) => $this->lineJson($l->item, $document->warehouse_id) + [
            'qty' => $l->qty, 'unit_price' => $l->unit_price, 'discount_type' => $l->discount_type, 'discount_input' => $l->discount_input,
            'tax_id' => $l->tax_id, 'tax_type' => $l->tax_type, 'description' => $l->description, 'sales_price' => $l->sales_price, 'profit_margin' => $l->profit_margin,
            'max_qty' => $max !== null ? max(0, ($parentQty[$l->item_id] ?? 0) - ($max[$l->item_id] ?? 0)) : null,
        ])->values();

        return view('retail.documents.form', $this->formData($document, $meta, $lines));
    }

    public function update(Request $request, Document $document, string $type)
    {
        $meta = $this->guard($document, $type, 'perm_edit');
        [$header, $rows, $payments] = $this->input($request, $meta, $document);
        DocumentService::save($document, $type, $header, $rows, $payments);
        $this->afterSave($document->fresh());

        return redirect()->route($meta['route'].'.show', $document)->with('success', $meta['one'].' updated.');
    }

    public function destroy(Document $document, string $type)
    {
        $meta = $this->guard($document, $type, 'perm_delete');
        if ($document->children()->whereIn('type', ['sale_return', 'purchase_return', 'invoice', 'purchase'])->exists()) {
            return back()->with('error', 'This '.strtolower($meta['one']).' has returns / conversions linked to it — delete those first.');
        }
        DocumentService::delete($document);

        return redirect()->route($meta['route'].'.index')->with('success', $meta['one'].' '.$document->number.' deleted. Stock and payments were reversed.');
    }

    public function bulkDelete(Request $request, string $type)
    {
        $meta = Document::meta($type);
        $this->need($meta['perm_delete']);
        $n = 0;
        foreach (Document::query()->where('type', $type)->whereIn('id', (array) $request->input('ids', []))->get() as $d) {
            if (! $d->children()->exists()) {
                DocumentService::delete($d);
                $n++;
            }
        }

        return back()->with('success', "$n document(s) deleted.");
    }

    /** Quotation open/cancel, delivery pending/delivered, purchase received/pending/ordered, invoice final/draft. */
    public function status(Request $request, Document $document, string $type)
    {
        $meta = $this->guard($document, $type, 'perm_edit');
        $status = $request->validate(['status' => ['required', 'in:'.implode(',', array_keys($meta['statuses']))]])['status'];
        DB::transaction(function () use ($document, $status) {
            \App\Support\Inventory\StockService::reverse($document);
            $document->update(['status' => $status]);
            DocumentService::applyStock($document);
            if ($document->type === 'purchase' && $document->update_prices && $status === 'received') {
                DocumentService::updateItemPrices($document);
            }
        });

        return back()->with('success', 'Status changed to '.$meta['statuses'][$status].'.');
    }

    public function addPayment(Request $request, Document $document, string $type)
    {
        $meta = $this->guard($document, $type, 'perm');
        abort_unless($meta['payments'], 404);
        $this->need($meta['side'] === 'sale' ? 'sales.payments' : 'purchase.payments');
        $data = $request->validate($this->paymentRules());
        if ($data['amount'] > $document->due() + 0.004) {
            return back()->with('error', 'Amount is more than the balance ('.number_format($document->due(), 2).').');
        }
        DB::transaction(function () use ($document, $data) {
            DocumentService::addPayment($document, $data);
            DocumentService::refreshPaid($document);
        });

        return back()->with('success', 'Payment recorded.');
    }

    public function deletePayment(Payment $payment)
    {
        $doc = $payment->document;
        $this->need(($doc && ! $doc->isSale()) || $payment->party_type === 'supplier' ? 'purchase.payments' : 'sales.payments');
        DB::transaction(function () use ($payment, $doc) {
            $payment->delete();
            if ($doc) {
                DocumentService::refreshPaid($doc);
            }
        });

        return back()->with('success', 'Payment deleted.');
    }

    /** Printable invoice: A4 (default) or 80mm thermal. ?pdf=1 downloads a PDF when dompdf is installed. */
    public function print(Request $request, Document $document, string $type)
    {
        $meta = $this->guard($document, $type, 'perm');
        $document->load('items.item.unit', 'items.item.variant', 'payments', 'customer', 'supplier', 'warehouse', 'salesman');
        $format = $request->input('format', $document->is_pos ? 'thermal' : 'a4');
        $data = [
            'meta' => $meta, 'doc' => $document, 'auto' => $request->boolean('auto'),
            'partyDue' => $meta['side'] === 'sale' ? ($document->customer_id ? Ledger::customerDue($document->customer_id) : 0) : ($document->supplier_id ? Ledger::supplierDue($document->supplier_id) : 0),
            'by' => User::query()->whereKey($document->created_by)->value('name'),
        ];
        $view = $format === 'thermal' ? 'retail.documents.print-thermal' : 'retail.documents.print-a4';

        if ($request->boolean('pdf') && class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data + ['pdf' => true])->download($document->number.'.pdf');
        }

        return view($view, $data + ['pdf' => $request->boolean('pdf')]);
    }

    /** Email the invoice to the customer through the company's SMTP. */
    public function email(Document $document, string $type)
    {
        $meta = $this->guard($document, $type, 'perm');
        $to = $document->customer?->email ?? $document->supplier?->email;
        if (! $to) {
            return back()->with('error', 'This '.($meta['side'] === 'sale' ? 'customer' : 'supplier').' has no email address.');
        }
        if (! Settings::mailer()) {
            return back()->with('error', 'Set up your SMTP in Settings → Notifications first.');
        }
        try {
            [$address, $name] = Settings::fromAddress();
            $html = view('retail.documents.print-a4', ['meta' => $meta, 'doc' => $document->load('items.item.unit', 'items.item.variant', 'payments', 'customer', 'supplier', 'warehouse', 'salesman'),
                'auto' => false, 'pdf' => true, 'partyDue' => 0, 'by' => null])->render();
            \Illuminate\Support\Facades\Mail::mailer(Settings::mailer())->html($html, function ($m) use ($to, $address, $name, $meta, $document) {
                $m->to($to)->from($address, $name)->subject($meta['one'].' '.$document->number.' — '.Settings::companyName());
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Email failed: '.\Illuminate\Support\Str::limit($e->getMessage(), 160));
        }

        return back()->with('success', $meta['one'].' emailed to '.$to.'.');
    }

    /** JSON: validate a coupon code for the sale form / POS. */
    public function checkCoupon(Request $request)
    {
        $this->need('sales.view', 'pos.sell', 'sales.create');
        try {
            $c = DocumentService::coupon((string) $request->code, $request->integer('customer_id') ?: null, (float) $request->subtotal, $request->integer('document_id') ?: null);
        } catch (ValidationException $e) {
            return response()->json(['ok' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['ok' => true] + $c);
    }

    /* ───────────── helpers ───────────── */

    private function guard(Document $document, string $type, string $permKey): array
    {
        abort_unless($document->type === $type, 404);
        $meta = Document::meta($type);
        $this->need($meta[$permKey]);

        return $meta;
    }

    private function perms(array $meta): array
    {
        $sale = $meta['side'] === 'sale';

        return [
            'create' => $this->can($meta['perm_create']), 'edit' => $this->can($meta['perm_edit']), 'delete' => $this->can($meta['perm_delete']),
            'pay' => $meta['payments'] && $this->can($sale ? 'sales.payments' : 'purchase.payments'),
            'invoice' => $this->can('sales.create'), 'return' => $this->can($sale ? 'sales.returns' : 'purchase.returns'),
            'purchase' => $this->can('purchase.create'),
        ];
    }

    /** item_id => qty already returned against $src (excluding $exceptId). */
    private function returnedQty(Document $src, ?int $exceptId = null): array
    {
        return DocumentItem::query()->whereHas('document', fn ($q) => $q->where('parent_id', $src->id)->whereIn('type', ['sale_return', 'purchase_return'])->when($exceptId, fn ($w) => $w->whereKeyNot($exceptId)))
            ->groupBy('item_id')->selectRaw('item_id, SUM(qty) q')->pluck('q', 'item_id')->map(fn ($v) => (float) $v)->all();
    }

    private function lineJson(?Item $item, ?int $warehouseId): array
    {
        return $item ? ItemController::json($item, $warehouseId) : ['id' => null, 'name' => 'Deleted item'];
    }

    private function formData(Document $doc, array $meta, $lines): array
    {
        $sale = $meta['side'] === 'sale';
        $party = $sale ? ($doc->customer_id ? Customer::query()->withTrashed()->find($doc->customer_id) : null) : ($doc->supplier_id ? Supplier::query()->withTrashed()->find($doc->supplier_id) : null);

        return [
            'meta' => $meta, 'doc' => $doc, 'lines' => $lines,
            'party' => $party ? ['id' => $party->id, 'name' => $party->company_name, 'mobile' => $party->mobile ?? $party->phone,
                'due' => $sale ? Ledger::customerDue($party->id, $doc->id) : Ledger::supplierDue($party->id, $doc->id),
                'advance' => $sale ? Ledger::customerAdvance($party->id) : 0, 'credit_limit' => $party->credit_limit ?? 0,
                'price_level_type' => $party->price_level_type ?? null, 'price_level' => $party->price_level ?? 0] : null,
            'warehouses' => Warehouse::options(),
            'salesmen' => $sale ? Salesman::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id') : collect(),
            'taxes' => TaxRate::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'rate']),
            'methods' => self::methods($sale && $meta['type'] === 'invoice'),
            'canDiscount' => ! $sale || $this->can('sales.discount'),
            'canPay' => $meta['payments'] && $this->can($sale ? 'sales.payments' : 'purchase.payments'),
            'roundOff' => Settings::enabled('round_off'),
        ];
    }

    /** Validate and split the form into header, rows and new payments. */
    private function input(Request $request, array $meta, ?Document $doc = null): array
    {
        $sale = $meta['side'] === 'sale';
        $type = $meta['type'];
        $data = $request->validate([
            'reference_no' => ['nullable', 'string', 'max:100'],
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', 'in:'.implode(',', array_keys($meta['statuses']))],
            'customer_id' => [$sale && $type !== 'invoice' ? 'required' : 'nullable', 'integer', 'exists:customers,id'],
            'supplier_id' => [$sale ? 'nullable' : 'required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:inv_warehouses,id'],
            'salesman_id' => ['nullable', 'integer', 'exists:rt_salesmen,id'],
            'parent_id' => ['nullable', 'integer', 'exists:rt_documents,id'],
            'other_charges_input' => ['nullable', 'numeric', 'min:0'],
            'other_charges_tax_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
            'discount_input' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'in:percentage,fixed'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array'],
            'items.*.item_id' => ['nullable', 'integer'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_type' => ['nullable', 'in:percentage,fixed'],
            'items.*.discount_input' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_id' => ['nullable', 'integer'],
            'items.*.tax_type' => ['nullable', 'in:exclusive,inclusive'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.sales_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.profit_margin' => ['nullable', 'numeric'],
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['nullable', 'string', 'max:50'],
            'payments.*.amount' => ['nullable', 'numeric', 'min:0'],
            'payments.*.note' => ['nullable', 'string', 'max:500'],
            'payments.*.cheque_no' => ['nullable', 'string', 'max:50'],
            'payments.*.cheque_date' => ['nullable', 'date'],
        ], [], ['customer_id' => 'customer', 'supplier_id' => 'supplier']);

        $header = collect($data)->except(['items', 'payments'])->all();
        $header['other_charges_input'] = (float) ($header['other_charges_input'] ?? 0);
        $header['discount_input'] = (float) ($header['discount_input'] ?? 0);
        $header['discount_type'] = $header['discount_type'] ?? 'percentage';
        if ($sale && $type === 'invoice' && empty($header['customer_id'])) {
            $header['customer_id'] = Customer::walkIn()->id;
        }
        if ($type === 'purchase') {
            $header['update_prices'] = $request->boolean('update_prices');
        }
        if ($doc) {
            unset($header['parent_id']);
        }
        $rows = array_values($data['items']);

        // Without sales.discount, prices and discounts come from the item (customer price level applied).
        if ($sale && ! $this->can('sales.discount')) {
            $items = Item::query()->whereIn('id', array_column($rows, 'item_id'))->get()->keyBy('id');
            $customer = ! empty($header['customer_id']) ? Customer::query()->find($header['customer_id']) : null;
            foreach ($rows as &$r) {
                if ($item = $items[$r['item_id'] ?? 0] ?? null) {
                    $r['unit_price'] = $customer ? $customer->priceFor($item->sales_price) : $item->sales_price;
                    $r['discount_type'] = $item->discount_type;
                    $r['discount_input'] = $item->discount;
                }
            }
            unset($r);
            $header['discount_input'] = 0;
        }

        // Returns can't exceed what was sold / bought.
        if (in_array($type, ['sale_return', 'purchase_return'], true) && ! empty($header['parent_id'] ?? $doc?->parent_id)) {
            $parent = Document::query()->with('items')->find($header['parent_id'] ?? $doc->parent_id);
            $sold = $parent->items->groupBy('item_id')->map->sum('qty');
            $returned = $this->returnedQty($parent, $doc?->id);
            foreach ($rows as $r) {
                if (empty($r['item_id']) || (float) ($r['qty'] ?? 0) <= 0) {
                    continue;
                }
                $max = (float) ($sold[$r['item_id']] ?? 0) - (float) ($returned[$r['item_id']] ?? 0);
                if ((float) $r['qty'] > $max + 0.0001) {
                    throw ValidationException::withMessages(['items' => 'Return qty is more than '.($sale ? 'sold' : 'purchased').' qty for '.(Item::withTrashed()->find($r['item_id'])->name ?? 'an item').' (max '.$max.').']);
                }
            }
        }

        $payments = $meta['payments'] && $this->can($sale ? 'sales.payments' : 'purchase.payments')
            ? array_values(array_filter($data['payments'] ?? [], fn ($p) => (float) ($p['amount'] ?? 0) > 0))
            : [];

        return [$header, $rows, $payments];
    }

    private function afterSave(Document $doc): void
    {
        // Converting a quotation / delivery note / purchase order marks the source as converted.
        if ($doc->parent && in_array($doc->parent->type, ['quotation', 'delivery', 'order'], true) && in_array($doc->type, ['invoice', 'purchase'], true)) {
            $doc->parent->update(['status' => 'converted']);
        }
    }
}
