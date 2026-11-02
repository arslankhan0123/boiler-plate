@extends('layouts.admin.main')
@php $money = fn ($v) => number_format((float) $v, 2); $cur = \App\Support\Settings::get('default_currency', 'PKR'); @endphp
@section('title', 'POS Sales')
@section('subtitle', 'POS')

@section('content')
<div class="app-content">
    <x-admin.page-header title="POS Sales" subtitle="Every sale made from the POS terminal.">
        @can('pos.sell')<x-admin.button icon="shop" :href="route('pos')">Open POS</x-admin.button>@endcan
    </x-admin.page-header>
    <div class="row g-4 mb-4">
        <div class="col-md-4"><x-admin.stat label="Sales" :value="number_format($sum->n ?? 0)" icon="receipt" /></div>
        <div class="col-md-4"><x-admin.stat label="Total" :value="$cur.' '.$money($sum->total)" icon="cash-stack" tone="success" /></div>
        <div class="col-md-4"><x-admin.stat label="On credit (due)" :value="$cur.' '.$money(($sum->total ?? 0) - ($sum->paid ?? 0))" icon="hourglass-split" tone="danger" /></div>
    </div>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:200px;min-height:40px" placeholder="Number or customer…">
                <select name="user" class="form-select" style="width:150px;min-height:40px" onchange="this.form.submit()"><option value="">All cashiers</option>@foreach ($users as $id => $n)<option value="{{ $id }}" @selected(request('user') == $id)>{{ $n }}</option>@endforeach</select>
                <select name="payment" class="form-select" style="width:130px;min-height:40px" onchange="this.form.submit()"><option value="">Any payment</option>@foreach (['paid', 'partial', 'unpaid'] as $p)<option value="{{ $p }}" @selected(request('payment') === $p)>{{ ucfirst($p) }}</option>@endforeach</select>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control" style="width:145px;min-height:40px" onchange="this.form.submit()">
                <input type="date" name="to" value="{{ request('to') }}" class="form-control" style="width:145px;min-height:40px" onchange="this.form.submit()">
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Time</th><th>Number</th><th>Customer</th><th>Items</th><th class="text-end">Total</th><th class="text-end">Paid</th><th>Payment</th><th>Cashier</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($rows as $d)
                <tr>
                    <td class="small text-nowrap">{{ $d->created_at?->format('d M Y h:i A') }}</td>
                    <td class="fw-semibold">{{ $d->number }}</td>
                    <td>{{ $d->partyName() }}</td><td>{{ $d->items_count }}</td>
                    <td class="text-end fw-semibold">{{ $money($d->grand_total) }}</td><td class="text-end">{{ $money($d->paid_amount) }}</td>
                    <td><x-admin.badge :tone="['paid' => 'success', 'partial' => 'warning', 'unpaid' => 'danger'][$d->payment_status] ?? 'muted'">{{ ucfirst($d->payment_status) }}</x-admin.badge></td>
                    <td class="small">{{ $users[$d->created_by] ?? '—' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('sales.invoices.print', [$d, 'format' => 'thermal']) }}" target="_blank" class="btn btn-action" title="Receipt"><i class="bi bi-receipt"></i></a>
                        @can('sales.view')<a href="{{ route('sales.invoices.show', $d) }}" class="btn btn-action" title="View"><i class="bi bi-eye"></i></a>@endcan
                        @if ($canEdit)<a href="{{ route('pos', ['edit' => $d->id]) }}" class="btn btn-action" title="Edit in POS"><i class="bi bi-pencil-square"></i></a>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9"><x-admin.empty icon="shop" text="No POS sales yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($rows->hasPages())
            <x-slot:footer>{{ $rows->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
