@extends('layouts.admin.main')
@php
    $cur = \App\Support\Settings::get('default_currency', 'PKR');
    $moneyCols = $money ?? [];
    $qtyCols = $qty ?? [];
    $numCols = array_merge($moneyCols, $qtyCols);
    $fmt = function ($k, $v) use ($moneyCols, $qtyCols) {
        if (in_array($k, $moneyCols, true)) return number_format((float) $v, 2);
        if (in_array($k, $qtyCols, true)) return \App\Support\Inventory\StockService::fmt((float) $v);
        return $v;
    };
    $linkCol = isset($columns['number']) ? 'number' : (isset($columns['customer']) ? 'customer' : (isset($columns['salesman']) ? 'salesman' : null));
@endphp
@section('title', $title)
@section('subtitle', 'Reports · '.$group)

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$title" :subtitle="$desc" :back="route('retail.reports')">
        <x-admin.button variant="light" icon="printer" type="button" onclick="window.print()">Print</x-admin.button>
        <x-admin.button variant="light" icon="filetype-csv" :href="request()->fullUrlWithQuery(['csv' => 1])">Export CSV</x-admin.button>
    </x-admin.page-header>

    <div class="app-card mb-4 no-print"><div class="app-card__body">
        <form method="GET" class="row g-2 align-items-end">
            @if (in_array('date', $filters))
                <div class="col-md-2"><label class="form-label small">From</label><input type="date" name="from" value="{{ $f['from'] }}" class="form-control"></div>
                <div class="col-md-2"><label class="form-label small">To</label><input type="date" name="to" value="{{ $f['to'] }}" class="form-control"></div>
            @endif
            @foreach ([['customer', 'Customer', $lists['customers']], ['supplier', 'Supplier', $lists['suppliers']], ['warehouse', 'Warehouse', $lists['warehouses']], ['salesman', 'Salesman', $lists['salesmen']],
                ['category', 'Category', $lists['categories']], ['brand', 'Brand', $lists['brands']], ['user', 'User', $lists['users']]] as [$k, $l, $opts])
                @if (in_array($k, $filters))
                    <div class="col-md-2"><label class="form-label small">{{ $l }}</label><select name="{{ $k }}" class="form-select"><option value="">All</option>@foreach ($opts as $id => $n)<option value="{{ $id }}" @selected(($f[$k] ?? '') == $id)>{{ $n }}</option>@endforeach</select></div>
                @endif
            @endforeach
            @if (in_array('method', $filters))<div class="col-md-2"><label class="form-label small">Method</label><select name="method" class="form-select"><option value="">All</option>@foreach ($lists['methods'] as $m)<option @selected(($f['method'] ?? '') === $m)>{{ $m }}</option>@endforeach</select></div>@endif
            @if (in_array('payment', $filters))<div class="col-md-2"><label class="form-label small">Payment</label><select name="payment" class="form-select"><option value="">All</option>@foreach (['paid', 'partial', 'unpaid'] as $p)<option value="{{ $p }}" @selected(($f['payment'] ?? '') === $p)>{{ ucfirst($p) }}</option>@endforeach</select></div>@endif
            @if (in_array('channel', $filters))<div class="col-md-2"><label class="form-label small">Channel</label><select name="channel" class="form-select"><option value="">All</option><option value="pos" @selected(($f['channel'] ?? '') === 'pos')>POS</option><option value="normal" @selected(($f['channel'] ?? '') === 'normal')>Invoices</option></select></div>@endif
            @if (in_array('item', $filters))<div class="col-md-2"><label class="form-label small">Item</label><input name="item" value="{{ $f['item'] ?? '' }}" class="form-control" placeholder="Name / code"></div>@endif
            @if (in_array('days', $filters))<div class="col-md-2"><label class="form-label small">Expiring within (days)</label><input type="number" name="days" value="{{ $f['days'] ?? 30 }}" class="form-control"></div>@endif
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Apply</button></div>
        </form>
    </div></div>

    <div class="print-head d-none"><h2>{{ \App\Support\Settings::companyName() }} — {{ $title }}</h2>@if ($f['from'])<div>{{ $f['from'] }} to {{ $f['to'] }}</div>@endif</div>

    @if (! empty($summary))
        <div class="row g-3 mb-4">@foreach ($summary as $k => $v)<div class="col-md-3"><x-admin.stat :label="$k" :value="$cur.' '.number_format($v, 2)" icon="cash" /></div>@endforeach</div>
    @endif

    <x-admin.card flush>
        <div class="table-responsive"><table class="table app-table table-hover">
            <thead><tr>@foreach ($columns as $k => $l)<th class="{{ in_array($k, $numCols) ? 'text-end' : '' }}">{{ $l }}</th>@endforeach</tr></thead>
            <tbody>
            @forelse ($rows as $r)
                <tr class="{{ ! empty($r['bold']) ? 'fw-bold table-light' : '' }}">
                    @foreach ($columns as $k => $l)
                        <td class="{{ in_array($k, $numCols) ? 'text-end' : '' }} {{ in_array($k, $moneyCols) && (float) ($r[$k] ?? 0) < 0 ? 'text-danger' : '' }}">
                            @if ($k === $linkCol && ! empty($r['url']))<a href="{{ $r['url'] }}" class="text-decoration-none">{{ $fmt($k, $r[$k] ?? '') }}</a>@else{{ $fmt($k, $r[$k] ?? '') }}@endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) }}"><x-admin.empty icon="bar-chart" text="No data for these filters." /></td></tr>
            @endforelse
            </tbody>
            @if (! empty($totals) && count($rows))
                <tfoot><tr class="fw-bold">@foreach ($columns as $k => $l)<td class="{{ in_array($k, $numCols) ? 'text-end' : '' }}">{{ $loop->first ? 'Total ('.count($rows).')' : (array_key_exists($k, $totals) ? $fmt($k, $totals[$k]) : '') }}</td>@endforeach</tr></tfoot>
            @endif
        </table></div>
    </x-admin.card>
</div>
@endsection

@push('styles')
<style>@media print { .no-print, .app-page-header__actions, aside, nav, header, footer, .app-sidebar { display: none !important; } .print-head { display: block !important; margin-bottom: 10px; } .app-card { box-shadow: none !important; } }</style>
@endpush
