@extends('layouts.admin.main')
@php $money = fn ($v) => number_format((float) $v, 2); $cur = \App\Support\Settings::get('default_currency', 'PKR'); @endphp
@section('title', 'Daily Summary')
@section('subtitle', 'POS')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Daily POS Summary" :subtitle="\Illuminate\Support\Carbon::parse($date)->format('l, d M Y')">
        <form method="GET" class="d-flex gap-2"><input type="date" name="date" value="{{ $date }}" class="form-control" onchange="this.form.submit()"></form>
        <x-admin.button variant="light" icon="printer" type="button" onclick="window.print()">Print</x-admin.button>
    </x-admin.page-header>
    <div class="row g-4 mb-4">
        <div class="col-md-3"><x-admin.stat label="Sales" :value="number_format($sum->n ?? 0)" icon="receipt" /></div>
        <div class="col-md-3"><x-admin.stat label="Total sales" :value="$cur.' '.$money($sum->total)" icon="cash-stack" tone="success" /></div>
        <div class="col-md-3"><x-admin.stat label="Discounts" :value="$cur.' '.$money($sum->disc)" icon="percent" tone="warning" /></div>
        <div class="col-md-3"><x-admin.stat label="On credit" :value="$cur.' '.$money(($sum->total ?? 0) - ($sum->paid ?? 0))" icon="hourglass-split" tone="danger" /></div>
    </div>
    <div class="row g-4">
        <div class="col-lg-6">
            <x-admin.card flush title="By payment method">
                <div class="table-responsive"><table class="table app-table">
                    <thead><tr><th>Method</th><th>Payments</th><th class="text-end">Collected</th><th class="text-end">Change given</th></tr></thead>
                    <tbody>
                    @forelse ($byMethod as $m)<tr><td class="fw-semibold">{{ $m->method }}</td><td>{{ $m->n }}</td><td class="text-end fw-semibold">{{ $money($m->amt) }}</td><td class="text-end">{{ $money($m->chg) }}</td></tr>
                    @empty<tr><td colspan="4"><x-admin.empty icon="cash" text="No payments." /></td></tr>@endforelse
                    </tbody>
                    @if ($byMethod->isNotEmpty())<tfoot><tr class="fw-bold"><td>Total</td><td>{{ $byMethod->sum('n') }}</td><td class="text-end">{{ $money($byMethod->sum('amt')) }}</td><td class="text-end">{{ $money($byMethod->sum('chg')) }}</td></tr></tfoot>@endif
                </table></div>
            </x-admin.card>
        </div>
        <div class="col-lg-6">
            <x-admin.card flush title="By cashier">
                <div class="table-responsive"><table class="table app-table">
                    <thead><tr><th>Cashier</th><th>Sales</th><th class="text-end">Total</th><th class="text-end">Paid</th></tr></thead>
                    <tbody>
                    @forelse ($byUser as $u)<tr><td class="fw-semibold">{{ $users[$u->created_by] ?? '—' }}</td><td>{{ $u->n }}</td><td class="text-end fw-semibold">{{ $money($u->total) }}</td><td class="text-end">{{ $money($u->paid) }}</td></tr>
                    @empty<tr><td colspan="4"><x-admin.empty icon="person" text="No sales." /></td></tr>@endforelse
                    </tbody>
                </table></div>
            </x-admin.card>
            <div class="app-card mt-4"><div class="app-card__body small">Tax collected: <b>{{ $money($sum->tax) }}</b> · Items voided: <b>{{ $voids->n ?? 0 }}</b> (value {{ $money($voids->v) }})</div></div>
        </div>
    </div>
</div>
@endsection
