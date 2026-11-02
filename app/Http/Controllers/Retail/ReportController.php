<?php

declare(strict_types=1);

namespace App\Http\Controllers\Retail;

use App\Models\Inventory\Attribute;
use App\Models\Inventory\Item;
use App\Models\Inventory\Stock;
use App\Models\Inventory\Warehouse;
use App\Models\Retail\Customer;
use App\Models\Retail\Document;
use App\Models\Retail\DocumentItem;
use App\Models\Retail\Payment;
use App\Models\Retail\Salesman;
use App\Models\Retail\Supplier;
use App\Models\Retail\VoidLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * All sales / purchase / inventory reports. Each report returns columns + rows + totals,
 * rendered by one view with filters, print and CSV export. Every report has its own permission.
 */
class ReportController extends RetailController
{
    /** key => [title, group, permission, description, filters] */
    public const REPORTS = [
        'sales' => ['Sales Report', 'Sales', 'sales.reports', 'Invoices with totals, paid and due.', ['date', 'customer', 'warehouse', 'salesman', 'payment', 'channel', 'user']],
        'sales_return' => ['Sales Return Report', 'Sales', 'sales.reports', 'Returns with refunded and due amounts.', ['date', 'customer', 'warehouse']],
        'item_sales' => ['Item Sales Report', 'Sales', 'sales.reports', 'Quantity and amount sold per item.', ['date', 'customer', 'warehouse', 'item', 'category', 'brand']],
        'return_items' => ['Returned Items Report', 'Sales', 'sales.reports', 'Items returned by customers.', ['date', 'customer', 'item']],
        'customer_orders' => ['Customer Orders', 'Sales', 'sales.reports', 'Invoices, sales and dues per customer.', ['date', 'customer']],
        'sales_payments' => ['Sales Payments', 'Sales', 'sales.reports', 'Money received by method.', ['date', 'customer', 'method']],
        'sales_tax' => ['Sales Tax Report', 'Sales', 'sales.reports', 'Tax collected per invoice.', ['date', 'customer']],
        'profit_items' => ['Profit by Item', 'Sales', 'sales.reports', 'Sales − cost per item (net of returns).', ['date', 'item', 'category', 'brand']],
        'profit_invoices' => ['Profit by Invoice', 'Sales', 'sales.reports', 'Sales − cost per invoice.', ['date', 'customer']],
        'profit_loss' => ['Profit & Loss', 'Sales', 'sales.reports', 'Sales, returns, purchases, cost of goods and gross profit.', ['date']],
        'seller_points' => ['Seller Points', 'Sales', 'sales.reports', 'Points / commission earned per salesman.', ['date', 'salesman']],
        'delivery_sheet' => ['Delivery / Load Sheet', 'Sales', 'sales.reports', 'Items to load for invoices in a period.', ['date', 'customer', 'warehouse', 'salesman']],
        'void_logs' => ['POS Void Log', 'Sales', 'sales.reports|pos.void_logs', 'Items removed from POS carts before checkout.', ['date', 'user']],
        'purchase' => ['Purchase Report', 'Purchase', 'purchase.reports', 'Purchases with totals, paid and due.', ['date', 'supplier', 'warehouse', 'payment']],
        'purchase_return' => ['Purchase Return Report', 'Purchase', 'purchase.reports', 'Returns to suppliers.', ['date', 'supplier', 'warehouse']],
        'purchase_payments' => ['Purchase Payments', 'Purchase', 'purchase.reports', 'Money paid to suppliers.', ['date', 'supplier', 'method']],
        'purchase_tax' => ['Purchase Tax Report', 'Purchase', 'purchase.reports', 'Tax paid per purchase.', ['date', 'supplier']],
        'supplier_items' => ['Supplier Items', 'Purchase', 'purchase.reports', 'Items bought from each supplier.', ['date', 'supplier', 'item']],
        'stock' => ['Stock Report', 'Inventory', 'inventory.reports', 'Stock, cost and sale value per item.', ['warehouse', 'item', 'category', 'brand']],
        'brand_stock' => ['Brand-wise Stock', 'Inventory', 'inventory.reports', 'Stock and value grouped by brand.', ['warehouse']],
        'expired_items' => ['Expired / Expiring Items', 'Inventory', 'inventory.reports', 'Items past or near their expiry date.', ['days']],
    ];

    public function index()
    {
        $this->need('sales.reports', 'purchase.reports', 'inventory.reports');
        $list = collect(self::REPORTS)->filter(fn ($r) => collect(explode('|', $r[2]))->contains(fn ($p) => $this->can($p)))->groupBy(fn ($r) => $r[1], preserveKeys: true);

        return view('retail.reports.index', ['groups' => $list]);
    }

    public function show(Request $request, string $report)
    {
        abort_unless(isset(self::REPORTS[$report]), 404);
        [$title, $group, $perm, $desc, $filters] = self::REPORTS[$report];
        $this->need(...explode('|', $perm));
        $from = $request->input('from', in_array('date', $filters, true) ? today()->startOfMonth()->toDateString() : null);
        $to = $request->input('to', in_array('date', $filters, true) ? today()->toDateString() : null);
        $f = $request->all() + ['from' => $from, 'to' => $to];

        $result = $this->{'r_'.$report}($f);

        if ($request->boolean('csv')) {
            return response()->streamDownload(function () use ($result) {
                $out = fopen('php://output', 'w');
                fputcsv($out, array_values($result['columns']));
                foreach ($result['rows'] as $r) {
                    fputcsv($out, array_map(fn ($k) => strip_tags((string) ($r[$k] ?? '')), array_keys($result['columns'])));
                }
                if (! empty($result['totals'])) {
                    fputcsv($out, array_map(fn ($k) => (string) ($result['totals'][$k] ?? ''), array_keys($result['columns'])));
                }
                fclose($out);
            }, $report.'-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
        }

        return view('retail.reports.show', $result + [
            'key' => $report, 'title' => $title, 'group' => $group, 'desc' => $desc, 'filters' => $filters, 'f' => $f,
            'lists' => [
                'customers' => in_array('customer', $filters, true) ? Customer::query()->orderBy('company_name')->pluck('company_name', 'id') : collect(),
                'suppliers' => in_array('supplier', $filters, true) ? Supplier::query()->orderBy('company_name')->pluck('company_name', 'id') : collect(),
                'warehouses' => in_array('warehouse', $filters, true) ? Warehouse::options() : [],
                'salesmen' => in_array('salesman', $filters, true) ? Salesman::query()->orderBy('name')->pluck('name', 'id') : collect(),
                'categories' => in_array('category', $filters, true) ? Attribute::options('category') : [],
                'brands' => in_array('brand', $filters, true) ? Attribute::options('brand') : [],
                'methods' => in_array('method', $filters, true) ? Payment::query()->distinct()->orderBy('method')->pluck('method') : collect(),
                'users' => in_array('user', $filters, true) ? User::query()->where('tenant_id', auth()->user()->tenant_id)->orderBy('name')->pluck('name', 'id') : collect(),
            ],
        ]);
    }

    /* ───────────── shared ───────────── */

    private function docs(string $type, array $f)
    {
        $party = in_array($type, ['invoice', 'sale_return'], true) ? 'customer_id' : 'supplier_id';

        return Document::query()->where('type', $type)->when(in_array($type, ['invoice'], true), fn ($q) => $q->where('status', 'final'))
            ->when($f['from'], fn ($q, $v) => $q->whereDate('date', '>=', $v))->when($f['to'], fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($f['customer'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))->when($f['supplier'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($f['warehouse'] ?? null, fn ($q, $v) => $q->where('warehouse_id', $v))->when($f['salesman'] ?? null, fn ($q, $v) => $q->where('salesman_id', $v))
            ->when($f['payment'] ?? null, fn ($q, $v) => $q->where('payment_status', $v))->when($f['user'] ?? null, fn ($q, $v) => $q->where('created_by', $v))
            ->when(($f['channel'] ?? null) === 'pos', fn ($q) => $q->where('is_pos', true))->when(($f['channel'] ?? null) === 'normal', fn ($q) => $q->where('is_pos', false));
    }

    private function lines(array $types, array $f)
    {
        return DocumentItem::query()->join('rt_documents as d', 'd.id', '=', 'rt_document_items.document_id')->join('inv_items as i', 'i.id', '=', 'rt_document_items.item_id')
            ->whereIn('d.type', $types)->whereNull('d.deleted_at')->where(fn ($q) => $q->where('d.type', '!=', 'invoice')->orWhere('d.status', 'final'))
            ->when($f['from'], fn ($q, $v) => $q->whereDate('d.date', '>=', $v))->when($f['to'], fn ($q, $v) => $q->whereDate('d.date', '<=', $v))
            ->when($f['customer'] ?? null, fn ($q, $v) => $q->where('d.customer_id', $v))->when($f['supplier'] ?? null, fn ($q, $v) => $q->where('d.supplier_id', $v))
            ->when($f['warehouse'] ?? null, fn ($q, $v) => $q->where('d.warehouse_id', $v))->when($f['salesman'] ?? null, fn ($q, $v) => $q->where('d.salesman_id', $v))
            ->when($f['category'] ?? null, fn ($q, $v) => $q->where('i.category_id', $v))->when($f['brand'] ?? null, fn ($q, $v) => $q->where('i.brand_id', $v))
            ->when($f['item'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('i.name', 'like', "%$v%")->orWhere('i.code', $v)->orWhere('i.barcode', $v)));
    }

    private function docReport($q, string $partyLabel): array
    {
        $rows = $q->with('customer', 'supplier', 'warehouse')->orderBy('date')->orderBy('id')->get();

        return [
            'columns' => ['date' => 'Date', 'number' => 'Number', 'party' => $partyLabel, 'warehouse' => 'Warehouse', 'subtotal' => 'Subtotal', 'discount' => 'Discount', 'tax' => 'Tax', 'total' => 'Grand total', 'paid' => 'Paid', 'due' => 'Due', 'status' => 'Payment'],
            'money' => ['subtotal', 'discount', 'tax', 'total', 'paid', 'due'],
            'rows' => $rows->map(fn (Document $d) => [
                'date' => $d->date->format('d M Y'), 'number' => $d->number, 'url' => route(Document::TYPES[$d->type]['route'].'.show', $d),
                'party' => $d->partyName(), 'warehouse' => $d->warehouse->name ?? '', 'subtotal' => $d->subtotal, 'discount' => $d->discount_amt + $d->coupon_amt,
                'tax' => $d->tax_total, 'total' => $d->grand_total, 'paid' => $d->paid_amount, 'due' => $d->due(), 'status' => ucfirst($d->payment_status),
            ])->all(),
            'totals' => ['subtotal' => $rows->sum('subtotal'), 'discount' => $rows->sum(fn ($d) => $d->discount_amt + $d->coupon_amt), 'tax' => $rows->sum('tax_total'),
                'total' => $rows->sum('grand_total'), 'paid' => $rows->sum('paid_amount'), 'due' => $rows->sum(fn ($d) => $d->due())],
        ];
    }

    /* ───────────── sales ───────────── */

    private function r_sales(array $f): array
    {
        return $this->docReport($this->docs('invoice', $f), 'Customer');
    }

    private function r_sales_return(array $f): array
    {
        return $this->docReport($this->docs('sale_return', $f), 'Customer');
    }

    private function r_item_sales(array $f): array
    {
        $rows = $this->lines(['invoice'], $f)->groupBy('rt_document_items.item_id', 'i.name', 'i.code')
            ->selectRaw('i.name, i.code, SUM(rt_document_items.qty) qty, SUM(rt_document_items.total) amount, SUM(rt_document_items.tax_amt) tax, SUM(rt_document_items.discount_amt) disc, COUNT(DISTINCT d.id) invoices')
            ->orderByDesc('qty')->get();

        return ['columns' => ['code' => 'Code', 'name' => 'Item', 'invoices' => 'Invoices', 'qty' => 'Qty sold', 'disc' => 'Discount', 'tax' => 'Tax', 'amount' => 'Amount'],
            'money' => ['disc', 'tax', 'amount'], 'qty' => ['qty'],
            'rows' => $rows->map(fn ($r) => (array) $r->getAttributes())->all(),
            'totals' => ['qty' => $rows->sum('qty'), 'disc' => $rows->sum('disc'), 'tax' => $rows->sum('tax'), 'amount' => $rows->sum('amount')]];
    }

    private function r_return_items(array $f): array
    {
        $rows = $this->lines(['sale_return'], $f)->orderBy('d.date')
            ->selectRaw('d.date, d.number, d.customer_id, i.name, i.code, rt_document_items.qty, rt_document_items.unit_price, rt_document_items.total')->get();
        $names = Customer::query()->withTrashed()->whereIn('id', $rows->pluck('customer_id'))->pluck('company_name', 'id');

        return ['columns' => ['date' => 'Date', 'number' => 'Return #', 'customer' => 'Customer', 'name' => 'Item', 'qty' => 'Qty', 'unit_price' => 'Price', 'total' => 'Amount'],
            'money' => ['unit_price', 'total'], 'qty' => ['qty'],
            'rows' => $rows->map(fn ($r) => ['date' => \Illuminate\Support\Carbon::parse($r->date)->format('d M Y'), 'number' => $r->number, 'customer' => $names[$r->customer_id] ?? '', 'name' => $r->name, 'qty' => $r->qty, 'unit_price' => $r->unit_price, 'total' => $r->total])->all(),
            'totals' => ['qty' => $rows->sum('qty'), 'total' => $rows->sum('total')]];
    }

    private function r_customer_orders(array $f): array
    {
        $rows = $this->docs('invoice', $f)->groupBy('customer_id')
            ->selectRaw('customer_id, COUNT(*) n, SUM(grand_total) total, SUM(paid_amount) paid, MAX(date) last')->orderByDesc('total')->get();
        $names = Customer::query()->withTrashed()->whereIn('id', $rows->pluck('customer_id'))->get()->keyBy('id');

        return ['columns' => ['customer' => 'Customer', 'mobile' => 'Mobile', 'n' => 'Invoices', 'last' => 'Last sale', 'total' => 'Sales', 'paid' => 'Paid', 'due' => 'Due (period)'],
            'money' => ['total', 'paid', 'due'],
            'rows' => $rows->map(fn ($r) => ['customer' => $names[$r->customer_id]->company_name ?? '—', 'url' => $names->has($r->customer_id) ? route('sales.customers.show', $r->customer_id) : null,
                'mobile' => $names[$r->customer_id]->mobile ?? '', 'n' => $r->n, 'last' => \Illuminate\Support\Carbon::parse($r->last)->format('d M Y'),
                'total' => $r->total, 'paid' => $r->paid, 'due' => $r->total - $r->paid])->all(),
            'totals' => ['n' => $rows->sum('n'), 'total' => $rows->sum('total'), 'paid' => $rows->sum('paid'), 'due' => $rows->sum('total') - $rows->sum('paid')]];
    }

    private function payReport(string $party, array $f): array
    {
        $rows = Payment::query()->with('document', 'party')->where('party_type', $party)
            ->when($f['from'], fn ($q, $v) => $q->whereDate('date', '>=', $v))->when($f['to'], fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($f[$party] ?? null, fn ($q, $v) => $q->where('party_id', $v))->when($f['method'] ?? null, fn ($q, $v) => $q->where('method', $v))
            ->orderBy('date')->orderBy('id')->get();
        $sign = fn ($p) => ($party === 'customer' ? ($p->direction === 'in' ? 1 : -1) : ($p->direction === 'out' ? 1 : -1)) * $p->amount;

        return ['columns' => ['date' => 'Date', 'code' => 'Receipt', 'party' => ucfirst($party), 'kind' => 'Type', 'doc' => 'Against', 'method' => 'Method', 'cheque' => 'Cheque', 'amount' => 'Amount'],
            'money' => ['amount'],
            'rows' => $rows->map(fn ($p) => ['date' => $p->date->format('d M Y'), 'code' => $p->code, 'party' => $p->party->company_name ?? '', 'kind' => Payment::KINDS[$p->kind] ?? $p->kind,
                'doc' => $p->document->number ?? '—', 'method' => $p->method, 'cheque' => $p->cheque_no ? $p->cheque_no.' ('.$p->cheque_status.')' : '', 'amount' => $sign($p)])->all(),
            'totals' => ['amount' => $rows->sum($sign)],
            'summary' => $rows->groupBy('method')->map(fn ($g) => $g->sum($sign))->all()];
    }

    private function r_sales_payments(array $f): array
    {
        return $this->payReport('customer', $f);
    }

    private function taxReport(string $type, array $f, string $party): array
    {
        $rows = $this->docs($type, $f)->with('customer', 'supplier')->where('tax_total', '>', 0)->orderBy('date')->get();

        return ['columns' => ['date' => 'Date', 'number' => 'Number', 'party' => $party, 'vat' => 'Tax no.', 'taxable' => 'Taxable amount', 'tax' => 'Tax', 'total' => 'Grand total'],
            'money' => ['taxable', 'tax', 'total'],
            'rows' => $rows->map(fn ($d) => ['date' => $d->date->format('d M Y'), 'number' => $d->number, 'url' => route(Document::TYPES[$d->type]['route'].'.show', $d), 'party' => $d->partyName(),
                'vat' => ($d->isSale() ? $d->customer?->vat_number : $d->supplier?->vat_number) ?? '', 'taxable' => $d->subtotal - $d->tax_total, 'tax' => $d->tax_total, 'total' => $d->grand_total])->all(),
            'totals' => ['taxable' => $rows->sum(fn ($d) => $d->subtotal - $d->tax_total), 'tax' => $rows->sum('tax_total'), 'total' => $rows->sum('grand_total')]];
    }

    private function r_sales_tax(array $f): array
    {
        return $this->taxReport('invoice', $f, 'Customer');
    }

    private function r_profit_items(array $f): array
    {
        $sold = $this->lines(['invoice'], $f)->groupBy('rt_document_items.item_id', 'i.name', 'i.code')
            ->selectRaw('rt_document_items.item_id id, i.name, i.code, SUM(rt_document_items.qty) qty, SUM(rt_document_items.total - rt_document_items.tax_amt) sales, SUM(rt_document_items.qty * rt_document_items.purchase_price) cost')->get()->keyBy('id');
        $ret = $this->lines(['sale_return'], $f)->groupBy('rt_document_items.item_id')
            ->selectRaw('rt_document_items.item_id id, SUM(rt_document_items.qty) qty, SUM(rt_document_items.total - rt_document_items.tax_amt) sales, SUM(rt_document_items.qty * rt_document_items.purchase_price) cost')->get()->keyBy('id');
        $rows = $sold->map(function ($r) use ($ret) {
            $x = $ret[$r->id] ?? null;
            $qty = $r->qty - ($x->qty ?? 0);
            $sales = $r->sales - ($x->sales ?? 0);
            $cost = $r->cost - ($x->cost ?? 0);

            return ['code' => $r->code, 'name' => $r->name, 'qty' => $qty, 'sales' => $sales, 'cost' => $cost, 'profit' => $sales - $cost, 'margin' => $sales > 0 ? round(($sales - $cost) / $sales * 100, 2).'%' : '—'];
        })->sortByDesc('profit')->values();

        return ['columns' => ['code' => 'Code', 'name' => 'Item', 'qty' => 'Net qty', 'sales' => 'Net sales (excl. tax)', 'cost' => 'Cost', 'profit' => 'Profit', 'margin' => 'Margin'],
            'money' => ['sales', 'cost', 'profit'], 'qty' => ['qty'], 'rows' => $rows->all(),
            'totals' => ['qty' => $rows->sum('qty'), 'sales' => $rows->sum('sales'), 'cost' => $rows->sum('cost'), 'profit' => $rows->sum('profit')]];
    }

    private function r_profit_invoices(array $f): array
    {
        $docs = $this->docs('invoice', $f)->with('customer')->withSum('items as cost', DB::raw('qty * purchase_price'))->orderBy('date')->get();
        $rows = $docs->map(function ($d) {
            $net = $d->grand_total - $d->tax_total - $d->other_charges_amt;

            return ['date' => $d->date->format('d M Y'), 'number' => $d->number, 'url' => route('sales.invoices.show', $d), 'party' => $d->partyName(),
                'sales' => $net, 'cost' => (float) $d->cost, 'profit' => $net - (float) $d->cost];
        });

        return ['columns' => ['date' => 'Date', 'number' => 'Invoice', 'party' => 'Customer', 'sales' => 'Sales (excl. tax & charges)', 'cost' => 'Cost', 'profit' => 'Profit'],
            'money' => ['sales', 'cost', 'profit'], 'rows' => $rows->all(),
            'totals' => ['sales' => $rows->sum('sales'), 'cost' => $rows->sum('cost'), 'profit' => $rows->sum('profit')]];
    }

    private function r_profit_loss(array $f): array
    {
        $sum = fn ($type, $col) => (float) $this->docs($type, $f)->sum($col);
        $cost = fn ($types) => (float) $this->lines($types, $f)->sum(DB::raw('rt_document_items.qty * rt_document_items.purchase_price'));
        $sales = $sum('invoice', 'grand_total');
        $salesTax = $sum('invoice', 'tax_total');
        $returns = $sum('sale_return', 'grand_total');
        $returnTax = $sum('sale_return', 'tax_total');
        $netSales = ($sales - $salesTax) - ($returns - $returnTax);
        $cogs = $cost(['invoice']) - $cost(['sale_return']);
        $rows = [
            ['label' => 'Sales (incl. tax)', 'amount' => $sales], ['label' => '− Sales tax', 'amount' => -$salesTax],
            ['label' => '− Sales returns (excl. tax)', 'amount' => -($returns - $returnTax)], ['label' => 'Net sales', 'amount' => $netSales, 'bold' => true],
            ['label' => '− Cost of goods sold', 'amount' => -$cogs], ['label' => 'Gross profit', 'amount' => $netSales - $cogs, 'bold' => true],
            ['label' => 'Discounts given (in sales)', 'amount' => $sum('invoice', 'discount_amt') + $sum('invoice', 'coupon_amt') + $sum('invoice', 'item_discount_total')],
            ['label' => 'Other charges collected', 'amount' => $sum('invoice', 'other_charges_amt')],
            ['label' => 'Purchases (incl. tax)', 'amount' => $sum('purchase', 'grand_total')], ['label' => 'Purchase returns', 'amount' => $sum('purchase_return', 'grand_total')],
            ['label' => 'Received from customers', 'amount' => (float) Payment::query()->where('party_type', 'customer')->where('direction', 'in')->whereBetween('date', [$f['from'], $f['to']])->sum('amount')],
            ['label' => 'Paid to suppliers', 'amount' => (float) Payment::query()->where('party_type', 'supplier')->where('direction', 'out')->whereBetween('date', [$f['from'], $f['to']])->sum('amount')],
        ];

        return ['columns' => ['label' => 'Item', 'amount' => 'Amount'], 'money' => ['amount'], 'rows' => $rows, 'totals' => []];
    }

    private function r_seller_points(array $f): array
    {
        $rows = $this->lines(['invoice'], $f)->whereNotNull('d.salesman_id')->groupBy('d.salesman_id')
            ->selectRaw('d.salesman_id, COUNT(DISTINCT d.id) invoices, SUM(rt_document_items.qty) qty, SUM(rt_document_items.total) sales, SUM(rt_document_items.seller_points) points')->get();
        $names = Salesman::query()->withTrashed()->whereIn('id', $rows->pluck('salesman_id'))->get()->keyBy('id');
        $out = $rows->map(function ($r) use ($names) {
            $s = $names[$r->salesman_id] ?? null;
            $commission = $s ? $r->sales * $s->commission_rate / 100 : 0;

            return ['salesman' => $s->name ?? '—', 'url' => $s ? route('sales.salesmen.show', $s) : null, 'invoices' => $r->invoices, 'qty' => $r->qty, 'sales' => $r->sales,
                'points' => $r->points, 'commission' => $commission, 'earned' => $r->points + $commission];
        });

        return ['columns' => ['salesman' => 'Salesman', 'invoices' => 'Invoices', 'qty' => 'Items sold', 'sales' => 'Sales', 'points' => 'Seller points', 'commission' => 'Commission', 'earned' => 'Total earned'],
            'money' => ['sales', 'points', 'commission', 'earned'], 'qty' => ['qty'], 'rows' => $out->all(),
            'totals' => ['invoices' => $out->sum('invoices'), 'qty' => $out->sum('qty'), 'sales' => $out->sum('sales'), 'points' => $out->sum('points'), 'commission' => $out->sum('commission'), 'earned' => $out->sum('earned')]];
    }

    private function r_delivery_sheet(array $f): array
    {
        $rows = $this->lines(['invoice', 'delivery'], $f)->groupBy('rt_document_items.item_id', 'i.name', 'i.code')
            ->selectRaw("i.code, i.name, SUM(rt_document_items.qty) qty, COUNT(DISTINCT d.id) docs, SUM(rt_document_items.total) amount")->orderBy('i.name')->get();

        return ['columns' => ['code' => 'Code', 'name' => 'Item', 'docs' => 'Invoices / DNs', 'qty' => 'Qty to load', 'amount' => 'Amount'],
            'money' => ['amount'], 'qty' => ['qty'], 'rows' => $rows->map(fn ($r) => (array) $r->getAttributes())->all(),
            'totals' => ['qty' => $rows->sum('qty'), 'amount' => $rows->sum('amount')]];
    }

    private function r_void_logs(array $f): array
    {
        $rows = VoidLog::query()->when($f['from'], fn ($q, $v) => $q->whereDate('created_at', '>=', $v))->when($f['to'], fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($f['user'] ?? null, fn ($q, $v) => $q->where('user_id', $v))->latest()->get();
        $users = User::query()->whereIn('id', $rows->pluck('user_id'))->pluck('name', 'id');
        $cust = Customer::query()->withTrashed()->whereIn('id', $rows->pluck('customer_id'))->pluck('company_name', 'id');

        return ['columns' => ['time' => 'Time', 'user' => 'Cashier', 'customer' => 'Customer', 'item' => 'Item', 'qty' => 'Qty', 'price' => 'Price', 'value' => 'Value', 'reason' => 'Reason'],
            'money' => ['price', 'value'], 'qty' => ['qty'],
            'rows' => $rows->map(fn ($r) => ['time' => $r->created_at->format('d M Y h:i A'), 'user' => $users[$r->user_id] ?? '', 'customer' => $cust[$r->customer_id] ?? '',
                'item' => $r->item_name, 'qty' => $r->qty, 'price' => $r->price, 'value' => $r->qty * $r->price, 'reason' => $r->reason])->all(),
            'totals' => ['qty' => $rows->sum('qty'), 'value' => $rows->sum(fn ($r) => $r->qty * $r->price)]];
    }

    /* ───────────── purchase ───────────── */

    private function r_purchase(array $f): array
    {
        return $this->docReport($this->docs('purchase', $f), 'Supplier');
    }

    private function r_purchase_return(array $f): array
    {
        return $this->docReport($this->docs('purchase_return', $f), 'Supplier');
    }

    private function r_purchase_payments(array $f): array
    {
        return $this->payReport('supplier', $f);
    }

    private function r_purchase_tax(array $f): array
    {
        return $this->taxReport('purchase', $f, 'Supplier');
    }

    private function r_supplier_items(array $f): array
    {
        $rows = $this->lines(['purchase'], $f)->groupBy('d.supplier_id', 'rt_document_items.item_id', 'i.name', 'i.code')
            ->selectRaw('d.supplier_id, i.name, i.code, SUM(rt_document_items.qty) qty, SUM(rt_document_items.total) amount, MAX(d.date) last, AVG(rt_document_items.unit_price) avg_price')
            ->orderBy('d.supplier_id')->orderBy('i.name')->get();
        $names = Supplier::query()->withTrashed()->whereIn('id', $rows->pluck('supplier_id'))->pluck('company_name', 'id');

        return ['columns' => ['supplier' => 'Supplier', 'code' => 'Code', 'name' => 'Item', 'qty' => 'Qty bought', 'avg_price' => 'Avg. cost', 'amount' => 'Amount', 'last' => 'Last purchase'],
            'money' => ['avg_price', 'amount'], 'qty' => ['qty'],
            'rows' => $rows->map(fn ($r) => ['supplier' => $names[$r->supplier_id] ?? '—', 'code' => $r->code, 'name' => $r->name, 'qty' => $r->qty, 'avg_price' => $r->avg_price, 'amount' => $r->amount,
                'last' => \Illuminate\Support\Carbon::parse($r->last)->format('d M Y')])->all(),
            'totals' => ['qty' => $rows->sum('qty'), 'amount' => $rows->sum('amount')]];
    }

    /* ───────────── inventory ───────────── */

    private function r_stock(array $f): array
    {
        $wid = $f['warehouse'] ?? null;
        $items = Item::query()->sellable()->where('is_service', false)->with('category', 'brand', 'unit', 'variant', 'stocks')
            ->when($f['category'] ?? null, fn ($q, $v) => $q->where('category_id', $v))->when($f['brand'] ?? null, fn ($q, $v) => $q->where('brand_id', $v))
            ->when($f['item'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->where('name', 'like', "%$v%")->orWhere('code', $v)->orWhere('barcode', $v)))
            ->orderBy('name')->get();
        $rows = $items->map(function ($i) use ($wid) {
            $q = $i->stockIn($wid ? (int) $wid : null);

            return ['code' => $i->code, 'name' => $i->displayName(), 'category' => $i->category->name ?? '', 'brand' => $i->brand->name ?? '', 'unit' => $i->unit->name ?? '',
                'qty' => $q, 'cost' => $i->purchase_price, 'price' => $i->sales_price, 'cost_value' => max(0, $q) * $i->purchase_price, 'sale_value' => max(0, $q) * $i->sales_price];
        });

        return ['columns' => ['code' => 'Code', 'name' => 'Item', 'category' => 'Category', 'brand' => 'Brand', 'qty' => 'Stock', 'unit' => 'Unit', 'cost' => 'Cost', 'price' => 'Sale price', 'cost_value' => 'Cost value', 'sale_value' => 'Sale value'],
            'money' => ['cost', 'price', 'cost_value', 'sale_value'], 'qty' => ['qty'], 'rows' => $rows->all(),
            'totals' => ['qty' => $rows->sum('qty'), 'cost_value' => $rows->sum('cost_value'), 'sale_value' => $rows->sum('sale_value')]];
    }

    private function r_brand_stock(array $f): array
    {
        $q = Stock::query()->join('inv_items as i', 'i.id', '=', 'inv_stocks.item_id')->whereNull('i.deleted_at')
            ->when($f['warehouse'] ?? null, fn ($w, $v) => $w->where('inv_stocks.warehouse_id', $v))
            ->groupBy('i.brand_id')->selectRaw('i.brand_id, COUNT(DISTINCT i.id) items, SUM(inv_stocks.qty) qty, SUM(GREATEST(inv_stocks.qty,0) * i.purchase_price) cost, SUM(GREATEST(inv_stocks.qty,0) * i.sales_price) sale')->get();
        $names = Attribute::query()->whereIn('id', $q->pluck('brand_id')->filter())->pluck('name', 'id');
        $rows = $q->map(fn ($r) => ['brand' => $names[$r->brand_id] ?? 'No brand', 'items' => $r->items, 'qty' => $r->qty, 'cost' => $r->cost, 'sale' => $r->sale])->sortByDesc('cost')->values();

        return ['columns' => ['brand' => 'Brand', 'items' => 'Items', 'qty' => 'Stock qty', 'cost' => 'Cost value', 'sale' => 'Sale value'],
            'money' => ['cost', 'sale'], 'qty' => ['qty'], 'rows' => $rows->all(),
            'totals' => ['items' => $rows->sum('items'), 'qty' => $rows->sum('qty'), 'cost' => $rows->sum('cost'), 'sale' => $rows->sum('sale')]];
    }

    private function r_expired_items(array $f): array
    {
        $days = (int) ($f['days'] ?? 30);
        $items = Item::query()->with('category')->whereNotNull('expire_date')->whereDate('expire_date', '<=', today()->addDays($days))->orderBy('expire_date')->get();

        return ['columns' => ['code' => 'Code', 'name' => 'Item', 'category' => 'Category', 'lot' => 'Lot no.', 'expiry' => 'Expiry', 'state' => 'Status', 'qty' => 'Stock', 'value' => 'Cost value'],
            'money' => ['value'], 'qty' => ['qty'],
            'rows' => $items->map(fn ($i) => ['code' => $i->code, 'name' => $i->displayName(), 'category' => $i->category->name ?? '', 'lot' => $i->lot_number, 'expiry' => $i->expire_date->format('d M Y'),
                'state' => $i->expire_date->isPast() ? 'Expired' : 'In '.today()->diffInDays($i->expire_date).' days', 'qty' => $i->stock, 'value' => max(0, $i->stock) * $i->purchase_price])->all(),
            'totals' => ['qty' => $items->sum('stock'), 'value' => $items->sum(fn ($i) => max(0, $i->stock) * $i->purchase_price)]];
    }
}
