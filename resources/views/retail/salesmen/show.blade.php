@extends('layouts.admin.main')
@php $money = fn ($v) => number_format((float) $v, 2); $cur = \App\Support\Settings::get('default_currency', 'PKR'); @endphp
@section('title', $s->name)
@section('subtitle', 'Sales · Salesmen')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$s->name" :subtitle="$s->code.' · '.($s->mobile ?: '')" :back="route('sales.salesmen.index')">
        <form method="GET" class="d-flex gap-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"><input type="date" name="to" value="{{ request('to') }}" class="form-control"><button class="btn btn-light">Filter</button></form>
    </x-admin.page-header>
    <div class="row g-4 mb-4">
        <div class="col-md-3"><x-admin.stat label="Sales" :value="$cur.' '.$money($sum['sales'])" icon="graph-up" /></div>
        <div class="col-md-3"><x-admin.stat label="Seller points" :value="$money($sum['points'])" icon="star" tone="warning" /></div>
        <div class="col-md-3"><x-admin.stat label="Commission" :value="$money($sum['commission'])" icon="percent" tone="success" /></div>
        <div class="col-md-3"><x-admin.stat label="Balance payable" :value="$cur.' '.$money($sum['balance'])" icon="wallet2" tone="danger" /></div>
    </div>
    <div class="row g-4">
        <div class="col-xl-8">
            <x-admin.card flush :title="'Invoices ('.$sum['invoices'].')'">
                <div class="table-responsive"><table class="table app-table table-hover">
                    <thead><tr><th>Date</th><th>Invoice</th><th>Customer</th><th class="text-end">Total</th><th class="text-end">Points</th></tr></thead>
                    <tbody>
                    @forelse ($sales as $d)
                        <tr><td class="small">{{ $d->date->format('d M Y') }}</td><td><a href="{{ route('sales.invoices.show', $d) }}">{{ $d->number }}</a></td><td>{{ $d->partyName() }}</td>
                            <td class="text-end">{{ $money($d->grand_total) }}</td><td class="text-end">{{ $money($d->items_sum_seller_points) }}</td></tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty icon="receipt" text="No sales yet." /></td></tr>
                    @endforelse
                    </tbody></table></div>
                @if ($sales->hasPages())
                    <x-slot:footer>{{ $sales->links('pagination::bootstrap-5') }}</x-slot:footer>
                @endif
            </x-admin.card>
        </div>
        <div class="col-xl-4 d-grid gap-4 align-content-start">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Pay / advance</h3>
                <form method="POST" action="{{ route('sales.salesmen.pay', $s) }}">@csrf
                    <x-admin.select name="kind" label="Type" :options="['payment' => 'Payment (settle earnings)', 'advance' => 'Advance']" required />
                    <div class="row g-2"><div class="col-6"><x-admin.input name="date" type="date" label="Date" :value="today()->toDateString()" required /></div>
                        <div class="col-6"><x-admin.input name="amount" type="number" step="any" min="0.01" label="Amount" required /></div></div>
                    <x-admin.select name="method" label="Method" :options="$methods" required />
                    <x-admin.input name="note" label="Note" optional />
                    <x-admin.button icon="cash" class="w-100">Save</x-admin.button>
                </form>
            </div></div>
            <x-admin.card flush title="Payments & advances">
                <div class="table-responsive"><table class="table app-table"><tbody>
                    @forelse ($payments as $p)
                        <tr><td class="small">{{ $p->date->format('d M Y') }}</td><td><x-admin.badge :tone="$p->kind === 'advance' ? 'warning' : 'success'">{{ ucfirst($p->kind) }}</x-admin.badge></td>
                            <td class="small">{{ $p->method }}</td><td class="text-end fw-semibold">{{ $money($p->amount) }}</td>
                            <td><x-admin.delete-button :action="route('sales.salesmen.payments.destroy', $p)" confirm="Delete?" /></td></tr>
                    @empty
                        <tr><td><x-admin.empty icon="cash" text="Nothing yet." /></td></tr>
                    @endforelse
                </tbody></table></div>
            </x-admin.card>
        </div>
    </div>
</div>
@endsection
