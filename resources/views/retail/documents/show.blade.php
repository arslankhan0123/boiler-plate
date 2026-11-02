@extends('layouts.admin.main')
@php
    $sale = $meta['side'] === 'sale';
    $cur = \App\Support\Settings::get('default_currency', 'PKR');
    $money = fn ($v) => number_format((float) $v, 2);
    $q = fn ($v) => \App\Support\Inventory\StockService::fmt((float) $v);
    $isReturn = in_array($doc->type, ['sale_return', 'purchase_return'], true);
    $party = $sale ? $doc->customer : $doc->supplier;
@endphp
@section('title', $meta['one'].' '.$doc->number)
@section('subtitle', ($sale ? 'Sales' : 'Purchase').' · '.$meta['many'])

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$meta['one'].' '.$doc->number" :subtitle="$doc->date->format('d M Y').' · '.$doc->partyName()" :back="route($meta['route'].'.index')">
        <div class="btn-group">
            <a href="{{ route($meta['route'].'.print', [$doc, 'format' => 'a4']) }}" target="_blank" class="btn btn-light"><i class="bi bi-printer me-1"></i> Print A4</a>
            <button type="button" class="btn btn-light dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" target="_blank" href="{{ route($meta['route'].'.print', [$doc, 'format' => 'thermal']) }}"><i class="bi bi-receipt me-2"></i>Thermal receipt</a></li>
                <li><a class="dropdown-item" target="_blank" href="{{ route($meta['route'].'.print', [$doc, 'format' => 'a4', 'pdf' => 1]) }}"><i class="bi bi-filetype-pdf me-2"></i>PDF</a></li>
                @if ($party?->email)<li><form method="POST" action="{{ route($meta['route'].'.email', $doc) }}">@csrf<button class="dropdown-item"><i class="bi bi-envelope me-2"></i>Email to {{ $party->email }}</button></form></li>@endif
            </ul>
        </div>
        @if (in_array($doc->type, ['quotation', 'delivery']) && $doc->status !== 'converted' && $can['invoice'])
            <x-admin.button variant="light" icon="receipt" :href="route('sales.invoices.create', ['from' => $doc->id])">Convert to invoice</x-admin.button>
        @endif
        @if ($doc->type === 'order' && $doc->status !== 'converted' && $can['purchase'])
            <x-admin.button variant="light" icon="cart-plus" :href="route('purchase.purchases.create', ['from' => $doc->id])">Convert to purchase</x-admin.button>
        @endif
        @if ($doc->type === 'invoice' && $doc->status === 'final' && $can['return'])
            <x-admin.button variant="light" icon="arrow-return-left" :href="route('sales.returns.create', ['from' => $doc->id])">Sales return</x-admin.button>
        @endif
        @if ($doc->type === 'purchase' && $can['return'])
            <x-admin.button variant="light" icon="arrow-return-right" :href="route('purchase.returns.create', ['from' => $doc->id])">Purchase return</x-admin.button>
        @endif
        @if ($can['edit'])<x-admin.button icon="pencil-square" :href="route($meta['route'].'.edit', $doc)">Edit</x-admin.button>@endif
    </x-admin.page-header>

    <div class="row g-4">
        <div class="col-xl-8 d-grid gap-4 align-content-start">
            <x-admin.card flush title="Items">
                <div class="table-responsive"><table class="table app-table">
                    <thead><tr><th>#</th><th>Item</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Discount</th><th class="text-end">Tax</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                    @foreach ($doc->items as $i => $l)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><span class="fw-semibold">{{ $l->item?->displayName() }}</span><div class="small text-body-secondary">{{ $l->item->code ?? '' }}{{ $l->description ? ' · '.$l->description : '' }}</div>
                                @if ($doc->type === 'purchase' && $doc->update_prices && $l->sales_price)<div class="small text-success">New sales price {{ $money($l->sales_price) }}</div>@endif</td>
                            <td class="text-end">{{ $q($l->qty) }} <span class="small text-body-secondary">{{ $l->item->unit->name ?? '' }}</span></td>
                            <td class="text-end">{{ $money($l->unit_price) }}</td>
                            <td class="text-end">{{ $l->discount_amt > 0 ? $money($l->discount_amt) : '—' }}</td>
                            <td class="text-end">{{ $l->tax_amt > 0 ? $money($l->tax_amt) : '—' }}<div class="small text-body-secondary">{{ $l->tax_rate > 0 ? +$l->tax_rate.'% '.$l->tax_type : '' }}</div></td>
                            <td class="text-end fw-semibold">{{ $money($l->total) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            </x-admin.card>

            @if ($meta['payments'])
                <x-admin.card flush :title="$isReturn ? 'Refunds' : 'Payments'">
                    <div class="table-responsive"><table class="table app-table">
                        <thead><tr><th>Date</th><th>Receipt</th><th>Method</th><th>Cheque</th><th>Note</th><th class="text-end">Amount</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($doc->payments as $p)
                            <tr>
                                <td class="small">{{ $p->date->format('d M Y') }}</td>
                                <td><a href="{{ route('payments.receipt', $p) }}" target="_blank" class="text-decoration-none">{{ $p->code }}</a></td>
                                <td>{{ $p->method }}</td>
                                <td class="small">@if ($p->cheque_no){{ $p->cheque_no }} {{ $p->cheque_date?->format('d M') }}
                                    <form method="POST" action="{{ route('payments.cheque', $p) }}" class="d-inline">@csrf @method('PATCH')
                                        <select name="cheque_status" class="form-select form-select-sm d-inline-block" style="width:110px" onchange="this.form.submit()" @disabled(! $can['pay'])>@foreach (['pending', 'cleared', 'bounced'] as $s)<option value="{{ $s }}" @selected($p->cheque_status === $s)>{{ ucfirst($s) }}</option>@endforeach</select></form>@else — @endif</td>
                                <td class="small text-body-secondary">{{ $p->note }}@if ($p->change_return > 0)<div>Change returned {{ $money($p->change_return) }}</div>@endif</td>
                                <td class="text-end fw-semibold {{ $p->cheque_status === 'bounced' ? 'text-decoration-line-through text-danger' : '' }}">{{ $money($p->amount) }}</td>
                                <td class="text-end">@if ($can['pay'])<x-admin.delete-button :action="route('payments.destroy', $p)" confirm="Delete this payment?" />@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="7"><x-admin.empty icon="cash" text="Nothing paid yet." /></td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                    @if ($can['pay'] && $doc->due() > 0)
                        <x-slot:footer>
                            <form method="POST" action="{{ route($meta['route'].'.payments.store', $doc) }}" class="row g-2 w-100 align-items-end">@csrf
                                <div class="col-md-2"><label class="form-label small">Date</label><input type="date" name="date" value="{{ today()->toDateString() }}" class="form-control form-control-sm" required></div>
                                <div class="col-md-2"><label class="form-label small">Method</label><select name="method" class="form-select form-select-sm">@foreach ($methods as $m)<option>{{ $m }}</option>@endforeach</select></div>
                                <div class="col-md-2"><label class="form-label small">Amount</label><input type="number" step="any" min="0.01" max="{{ $doc->due() }}" name="amount" value="{{ round($doc->due(), 2) }}" class="form-control form-control-sm" required></div>
                                <div class="col-md-2"><label class="form-label small">Cheque no.</label><input name="cheque_no" class="form-control form-control-sm"></div>
                                <div class="col-md-2"><label class="form-label small">Note</label><input name="note" class="form-control form-control-sm"></div>
                                <div class="col-md-2"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg"></i> {{ $isReturn ? 'Refund' : 'Add payment' }}</button></div>
                                @if ($advance > 0 && $doc->type === 'invoice')<div class="col-12 small text-success">Customer has {{ $cur }} {{ $money($advance) }} advance — choose method "Advance" to use it.</div>@endif
                            </form>
                        </x-slot:footer>
                    @endif
                </x-admin.card>
            @endif

            @if ($doc->children->isNotEmpty() || $doc->parent)
                <x-admin.card title="Linked documents">
                    <ul class="mb-0 small">
                        @if ($doc->parent)<li>From {{ \App\Models\Retail\Document::TYPES[$doc->parent->type]['one'] }} <a href="{{ route(\App\Models\Retail\Document::TYPES[$doc->parent->type]['route'].'.show', $doc->parent) }}">{{ $doc->parent->number }}</a></li>@endif
                        @foreach ($doc->children as $c)<li>{{ \App\Models\Retail\Document::TYPES[$c->type]['one'] }} <a href="{{ route(\App\Models\Retail\Document::TYPES[$c->type]['route'].'.show', $c) }}">{{ $c->number }}</a> — {{ $c->date->format('d M Y') }} · {{ $money($c->grand_total) }}</li>@endforeach
                    </ul>
                </x-admin.card>
            @endif
        </div>

        <div class="col-xl-4 d-grid gap-4 align-content-start">
            <div class="app-card"><div class="app-card__body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <x-admin.badge :tone="in_array($doc->status, ['final', 'received', 'delivered', 'converted']) ? 'success' : 'warning'">{{ $meta['statuses'][$doc->status] ?? $doc->status }}</x-admin.badge>
                    @if ($meta['payments'])<x-admin.badge :tone="['paid' => 'success', 'partial' => 'warning', 'unpaid' => 'danger'][$doc->payment_status] ?? 'muted'">{{ ucfirst($doc->payment_status) }}</x-admin.badge>@endif
                </div>
                <table class="table table-sm mb-0 small">
                    <tr><td>Subtotal</td><td class="text-end">{{ $money($doc->subtotal) }}</td></tr>
                    @if ($doc->item_discount_total > 0)<tr><td class="text-body-secondary">(item discounts {{ $money($doc->item_discount_total) }})</td><td></td></tr>@endif
                    @if ($doc->other_charges_amt > 0)<tr><td>Other charges</td><td class="text-end">{{ $money($doc->other_charges_amt) }}</td></tr>@endif
                    @if ($doc->discount_amt > 0)<tr><td>Discount {{ $doc->discount_type === 'percentage' ? '('.+$doc->discount_input.'%)' : '' }}</td><td class="text-end text-danger">−{{ $money($doc->discount_amt) }}</td></tr>@endif
                    @if ($doc->coupon_amt > 0)<tr><td>Coupon {{ $doc->coupon_code }}</td><td class="text-end text-danger">−{{ $money($doc->coupon_amt) }}</td></tr>@endif
                    @if ($doc->round_off != 0)<tr><td>Round off</td><td class="text-end">{{ $money($doc->round_off) }}</td></tr>@endif
                    <tr><td class="fw-bold">Grand total</td><td class="text-end fw-bold fs-5">{{ $cur }} {{ $money($doc->grand_total) }}</td></tr>
                    <tr><td>Tax included</td><td class="text-end">{{ $money($doc->tax_total) }}</td></tr>
                    @if ($meta['payments'])
                        <tr><td>{{ $isReturn ? 'Refunded' : 'Paid' }}</td><td class="text-end text-success">{{ $money($doc->paid_amount) }}</td></tr>
                        <tr><td class="fw-bold">Balance</td><td class="text-end fw-bold {{ $doc->due() > 0 ? 'text-danger' : '' }}">{{ $money($doc->due()) }}</td></tr>
                    @endif
                </table>
            </div></div>

            <x-admin.card title="Details">
                <dl class="row small mb-0">
                    <dt class="col-5 text-body-secondary">{{ $sale ? 'Customer' : 'Supplier' }}</dt>
                    <dd class="col-7 text-end">@if ($party)<a href="{{ $sale ? route('sales.customers.show', $party) : route('purchase.suppliers.show', $party) }}">{{ $party->company_name }}</a>@else — @endif</dd>
                    @if ($party)<dt class="col-5 text-body-secondary">Total due now</dt><dd class="col-7 text-end {{ $partyDue > 0 ? 'text-danger fw-semibold' : '' }}">{{ $money($partyDue) }}</dd>@endif
                    <dt class="col-5 text-body-secondary">Warehouse</dt><dd class="col-7 text-end">{{ $doc->warehouse->name ?? '—' }}</dd>
                    @if ($doc->salesman)<dt class="col-5 text-body-secondary">Salesman</dt><dd class="col-7 text-end">{{ $doc->salesman->name }}</dd>@endif
                    @if ($doc->reference_no)<dt class="col-5 text-body-secondary">Reference</dt><dd class="col-7 text-end">{{ $doc->reference_no }}</dd>@endif
                    @if ($doc->due_date)<dt class="col-5 text-body-secondary">{{ $doc->type === 'quotation' ? 'Valid until' : 'Due date' }}</dt><dd class="col-7 text-end">{{ $doc->due_date->format('d M Y') }}</dd>@endif
                    <dt class="col-5 text-body-secondary">Created by</dt><dd class="col-7 text-end">{{ $by ?? '—' }}</dd>
                    <dt class="col-5 text-body-secondary">Created</dt><dd class="col-7 text-end">{{ $doc->created_at?->format('d M Y h:i A') }}</dd>
                </dl>
                @if ($can['edit'] && count($meta['statuses']) > 1)
                    <form method="POST" action="{{ route($meta['route'].'.status', $doc) }}" class="d-flex gap-2 mt-3">@csrf @method('PATCH')
                        <select name="status" class="form-select form-select-sm">@foreach ($meta['statuses'] as $k => $l)<option value="{{ $k }}" @selected($doc->status === $k)>{{ $l }}</option>@endforeach</select>
                        <button class="btn btn-sm btn-light text-nowrap">Change status</button>
                    </form>
                @endif
                @if ($doc->note)<p class="small mt-3 mb-0" style="white-space:pre-line"><b>Note:</b> {{ $doc->note }}</p>@endif
                @if ($can['delete'])<div class="mt-3 d-flex align-items-center gap-2"><x-admin.delete-button :action="route($meta['route'].'.destroy', $doc)" :confirm="'Delete '.$doc->number.'? Stock and payments will be reversed.'" /><span class="small text-body-secondary">Delete (reverses stock & payments)</span></div>@endif
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
