@extends('layouts.admin.main')
@php $m = fn ($v) => number_format((float) $v, 2); $cur = \App\Support\Settings::get('default_currency', 'PKR'); @endphp
@section('title', 'Cash Registers')
@section('subtitle', 'POS')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Cash Registers" subtitle="Open a shift with a float, record pay-ins/outs, close with a counted drawer.">
        @if ($current)
            <x-admin.button icon="box-arrow-in-right" :href="route('pos.registers.show', $current)">My open register</x-admin.button>
        @elseif ($canOpen)
            <x-admin.button icon="unlock" :href="route('pos.registers.open')">Open register</x-admin.button>
        @endif
    </x-admin.page-header>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex flex-wrap gap-2">
                <select name="status" class="form-select" style="width:130px;min-height:40px" onchange="this.form.submit()"><option value="">Any status</option><option value="open" @selected(request('status') === 'open')>Open</option><option value="closed" @selected(request('status') === 'closed')>Closed</option></select>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control" style="width:150px;min-height:40px" onchange="this.form.submit()">
                <input type="date" name="to" value="{{ request('to') }}" class="form-control" style="width:150px;min-height:40px" onchange="this.form.submit()">
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Opened</th><th>Warehouse</th><th>By</th><th>Status</th><th class="text-end">Opening</th><th class="text-end">Cash sales</th><th class="text-end">Expected</th><th class="text-end">Counted</th><th class="text-end">Over/Short</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td class="small text-nowrap">{{ $r->opened_at->format('d M Y h:i A') }}@if ($r->closed_at)<div class="text-body-secondary">closed {{ $r->closed_at->format('d M h:i A') }}</div>@endif</td>
                    <td>{{ $r->warehouse->name ?? '—' }}</td>
                    <td class="small">{{ $users[$r->opened_by] ?? '—' }}</td>
                    <td><x-admin.badge :tone="$r->status === 'open' ? 'success' : 'muted'">{{ ucfirst($r->status) }}</x-admin.badge></td>
                    <td class="text-end">{{ $m($r->opening_cash) }}</td>
                    <td class="text-end">{{ $r->status === 'closed' ? $m($r->cash_sales) : '—' }}</td>
                    <td class="text-end">{{ $r->status === 'closed' ? $m($r->expected_cash) : '—' }}</td>
                    <td class="text-end">{{ $r->status === 'closed' ? $m($r->counted_cash) : '—' }}</td>
                    <td class="text-end fw-bold {{ $r->over_short < 0 ? 'text-danger' : ($r->over_short > 0 ? 'text-success' : '') }}">{{ $r->status === 'closed' ? ($r->over_short > 0 ? '+' : '').$m($r->over_short) : '—' }}</td>
                    <td class="text-end"><a href="{{ route('pos.registers.show', $r) }}" class="btn btn-action"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="10"><x-admin.empty icon="cash-coin" text="No register sessions yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($rows->hasPages())
            <x-slot:footer>{{ $rows->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
