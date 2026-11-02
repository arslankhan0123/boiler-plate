@extends('layouts.admin.main')
@php
    $c = $side === 'customer';
    $r = $c ? 'sales.customers' : 'purchase.suppliers';
    $cur = \App\Support\Settings::get('default_currency', 'PKR');
    $money = fn ($v) => number_format((float) $v, 2);
@endphp
@section('title', $party->company_name)
@section('subtitle', $c ? 'Sales · Customers' : 'Purchase · Suppliers')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$party->company_name" :subtitle="trim(($party->code ?? '').' '.($party->mobile ?? $party->phone ?? ''))" :back="route($r.'.index')">
        <x-admin.button variant="light" icon="journal-text" :href="route($r.'.statement', $party)">Statement</x-admin.button>
        @if ($c && auth()->user()->can('sales.create'))<x-admin.button variant="light" icon="receipt" :href="route('sales.invoices.create')">New invoice</x-admin.button>@endif
        @if (! $c && auth()->user()->can('purchase.create'))<x-admin.button variant="light" icon="cart-plus" :href="route('purchase.purchases.create')">New purchase</x-admin.button>@endif
        @if ($canEdit)<x-admin.button icon="pencil-square" :href="route($r.'.edit', $party)">Edit</x-admin.button>@endif
    </x-admin.page-header>

    <div class="row g-4 mb-4">
        <div class="col-md-3"><x-admin.stat :label="$c ? 'Total due (receivable)' : 'Total due (payable)'" :value="$cur.' '.$money($due)" icon="hourglass-split" :tone="$due > 0 ? 'danger' : 'success'" /></div>
        <div class="col-md-3"><x-admin.stat :label="$c ? 'Total sales' : 'Total purchases'" :value="$cur.' '.$money($stats->total ?? 0)" icon="graph-up" /></div>
        <div class="col-md-3"><x-admin.stat :label="$c ? 'Invoices' : 'Purchases'" :value="number_format($stats->n ?? 0)" icon="receipt" /></div>
        <div class="col-md-3">@if ($c)<x-admin.stat label="Advance balance" :value="$cur.' '.$money($advance)" icon="piggy-bank" tone="success" />@else<x-admin.stat label="Opening due" :value="$cur.' '.$money($openingDue)" icon="flag" tone="warning" />@endif</div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8 d-grid gap-4 align-content-start">
            <x-admin.card flush title="Recent documents">
                <x-slot:actions><a href="{{ route($c ? 'sales.invoices.index' : 'purchase.purchases.index', ['party' => $party->id]) }}" class="btn btn-sm btn-light">All</a></x-slot:actions>
                <div class="table-responsive"><table class="table app-table table-hover">
                    <thead><tr><th>Date</th><th>Document</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Due</th></tr></thead>
                    <tbody>
                    @forelse ($documents as $d)
                        <tr><td class="small">{{ $d->date->format('d M Y') }}</td>
                            <td><a href="{{ route(\App\Models\Retail\Document::TYPES[$d->type]['route'].'.show', $d) }}" class="fw-semibold text-decoration-none">{{ $d->number }}</a> <span class="small text-body-secondary">{{ \App\Models\Retail\Document::TYPES[$d->type]['one'] }}</span></td>
                            <td class="text-end">{{ $money($d->grand_total) }}</td><td class="text-end">{{ $money($d->paid_amount) }}</td>
                            <td class="text-end {{ $d->due() > 0 && \App\Models\Retail\Document::TYPES[$d->type]['payments'] ? 'text-danger fw-semibold' : '' }}">{{ \App\Models\Retail\Document::TYPES[$d->type]['payments'] ? $money($d->due()) : '—' }}</td></tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty icon="receipt" text="No documents yet." /></td></tr>
                    @endforelse
                    </tbody></table></div>
            </x-admin.card>
            <x-admin.card flush title="Payments">
                <div class="table-responsive"><table class="table app-table">
                    <thead><tr><th>Date</th><th>Receipt</th><th>Type</th><th>Against</th><th>Method</th><th class="text-end">Amount</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($payments as $p)
                        <tr><td class="small">{{ $p->date->format('d M Y') }}</td><td><a href="{{ route('payments.receipt', $p) }}" target="_blank">{{ $p->code }}</a></td>
                            <td><x-admin.badge :tone="$p->kind === 'advance' ? 'success' : ($p->kind === 'refund' ? 'warning' : 'primary')">{{ \App\Models\Retail\Payment::KINDS[$p->kind] ?? $p->kind }}</x-admin.badge></td>
                            <td class="small">{{ $p->document->number ?? '—' }}</td><td class="small">{{ $p->method }}{{ $p->cheque_no ? ' · chq '.$p->cheque_no.' ('.$p->cheque_status.')' : '' }}</td>
                            <td class="text-end fw-semibold">{{ $money($p->amount) }}</td>
                            <td class="text-end">@if ($canPay)<x-admin.delete-button :action="route('payments.destroy', $p)" confirm="Delete this payment?" />@endif</td></tr>
                    @empty
                        <tr><td colspan="7"><x-admin.empty icon="cash" text="No payments yet." /></td></tr>
                    @endforelse
                    </tbody></table></div>
            </x-admin.card>
            @if ($c && $coupons->isNotEmpty())
                <x-admin.card flush title="Coupons">
                    <div class="table-responsive"><table class="table app-table"><tbody>
                        @foreach ($coupons as $cc)<tr><td class="fw-semibold">{{ $cc->code }}</td><td>{{ $cc->coupon->name ?? '' }} ({{ $cc->coupon?->label() }})</td><td>{{ $cc->expire_date?->format('d M Y') ?? 'No expiry' }}</td>
                            <td>@if ($cc->used_document_id)<x-admin.badge tone="muted">Used</x-admin.badge>@else<x-admin.badge tone="success">Available</x-admin.badge>@endif</td></tr>@endforeach
                    </tbody></table></div>
                </x-admin.card>
            @endif
        </div>
        <div class="col-xl-4 d-grid gap-4 align-content-start">
            @if ($canPay)
                <div class="app-card"><div class="app-card__body">
                    <h3 class="app-aside-title">{{ $c ? 'Receive payment' : 'Pay supplier' }}</h3>
                    <p class="small text-body-secondary">Settles the opening balance first, then the oldest unpaid {{ $c ? 'invoices' : 'purchases' }}.{{ $c ? ' Extra is kept as advance.' : '' }}</p>
                    <form method="POST" action="{{ route($c ? 'sales.customers.receive' : 'purchase.suppliers.pay', $party) }}">@csrf
                        <x-admin.input name="date" type="date" label="Date" :value="today()->toDateString()" required />
                        <x-admin.select name="method" label="Method" :options="$methods" required />
                        <x-admin.input name="amount" type="number" step="any" min="0.01" label="Amount" :value="$due > 0 ? round($due, 2) : ''" required />
                        <div class="row g-2"><div class="col-6"><x-admin.input name="cheque_no" label="Cheque no." optional /></div><div class="col-6"><x-admin.input name="cheque_date" type="date" label="Cheque date" optional /></div></div>
                        <x-admin.input name="note" label="Note" optional />
                        <x-admin.button icon="cash" class="w-100">{{ $c ? 'Receive' : 'Pay' }}</x-admin.button>
                    </form>
                </div></div>
                @if ($c)
                    <div class="app-card"><div class="app-card__body">
                        <h3 class="app-aside-title">Add advance</h3>
                        <form method="POST" action="{{ route('sales.customers.advance', $party) }}">@csrf
                            <div class="row g-2">
                                <div class="col-6"><x-admin.input name="date" type="date" label="Date" :value="today()->toDateString()" required /></div>
                                <div class="col-6"><x-admin.input name="amount" type="number" step="any" min="0.01" label="Amount" required /></div>
                            </div>
                            <x-admin.select name="method" label="Method" :options="$methods" required />
                            <x-admin.input name="note" label="Note" optional />
                            <x-admin.button variant="light" icon="piggy-bank" class="w-100">Save advance</x-admin.button>
                        </form>
                    </div></div>
                @endif
            @endif
            <x-admin.card title="Details">
                <dl class="row small mb-0">
                    @foreach (['Contact' => $party->contact_person, 'Mobile' => $party->mobile ?? null, 'Phone' => $party->phone, 'Email' => $party->email, 'Tax no.' => $party->vat_number,
                        'Address' => trim(($party->address ?? $party->street ?? '').' '.($party->city ?? '')), 'Ship to' => $c ? trim(($party->ship_address ?? '').' '.($party->ship_city ?? '')) : null,
                        'Opening balance' => $money($party->opening_balance), 'Opening still due' => $money($openingDue),
                        'Credit limit' => $c && $party->credit_limit > 0 ? $money($party->credit_limit) : null,
                        'Price level' => $c && $party->price_level_type ? ucfirst($party->price_level_type).' '.+$party->price_level.'%' : null] as $k => $v)
                        @if ($v !== null && $v !== '')<dt class="col-5 text-body-secondary">{{ $k }}</dt><dd class="col-7 text-end">{{ $v }}</dd>@endif
                    @endforeach
                </dl>
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
