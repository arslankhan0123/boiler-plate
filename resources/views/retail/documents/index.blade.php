@extends('layouts.admin.main')
@php
    $sale = $meta['side'] === 'sale';
    $cur = \App\Support\Settings::get('default_currency', 'PKR');
    $money = fn ($v) => number_format((float) $v, 2);
    $tone = ['paid' => 'success', 'partial' => 'warning', 'unpaid' => 'danger'];
@endphp
@section('title', $meta['many'])
@section('subtitle', $sale ? 'Sales' : 'Purchase')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$meta['many']" :subtitle="$party ? 'For '.$party->company_name : null">
        @if ($meta['type'] === 'invoice' && auth()->user()->can('pos.sell') && \App\Support\ModuleAccess::moduleAvailable(auth()->user(), 'pos'))<x-admin.button variant="light" icon="shop" :href="route('pos')">POS</x-admin.button>@endif
        @if ($can['create'])<x-admin.button icon="plus-lg" :href="route($meta['route'].'.create')">New {{ strtolower($meta['one']) }}</x-admin.button>@endif
    </x-admin.page-header>

    <div class="row g-4 mb-4">
        <div class="col-md-4"><x-admin.stat :label="$meta['many']" :value="number_format($sum->n ?? 0)" :icon="$meta['icon']" /></div>
        <div class="col-md-4"><x-admin.stat label="Total" :value="$cur.' '.$money($sum->total)" icon="cash-stack" tone="success" /></div>
        @if ($meta['payments'])<div class="col-md-4"><x-admin.stat :label="in_array($meta['type'], ['sale_return', 'purchase_return']) ? 'Not yet refunded' : 'Due'" :value="$cur.' '.$money(($sum->total ?? 0) - ($sum->paid ?? 0))" icon="hourglass-split" tone="danger" /></div>@endif
    </div>

    <form method="POST" action="{{ route($meta['route'].'.bulk-delete') }}" id="bulkForm">@csrf</form>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex flex-wrap gap-2">
                @if (request('party'))<input type="hidden" name="party" value="{{ request('party') }}">@endif
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:210px;min-height:40px" placeholder="Number, ref, {{ $sale ? 'customer' : 'supplier' }}…">
                <select name="status" class="form-select" style="width:140px;min-height:40px" onchange="this.form.submit()"><option value="">Any status</option>@foreach ($meta['statuses'] as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach</select>
                @if ($meta['payments'])<select name="payment" class="form-select" style="width:130px;min-height:40px" onchange="this.form.submit()"><option value="">Any payment</option>@foreach (['paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid'] as $k => $l)<option value="{{ $k }}" @selected(request('payment') === $k)>{{ $l }}</option>@endforeach</select>@endif
                @if ($meta['type'] === 'invoice')<select name="channel" class="form-select" style="width:130px;min-height:40px" onchange="this.form.submit()"><option value="">All sales</option><option value="pos" @selected(request('channel') === 'pos')>POS only</option><option value="normal" @selected(request('channel') === 'normal')>Invoices only</option></select>@endif
                @if ($salesmen->isNotEmpty())<select name="salesman" class="form-select" style="width:140px;min-height:40px" onchange="this.form.submit()"><option value="">Any salesman</option>@foreach ($salesmen as $id => $n)<option value="{{ $id }}" @selected(request('salesman') == $id)>{{ $n }}</option>@endforeach</select>@endif
                <select name="warehouse" class="form-select" style="width:150px;min-height:40px" onchange="this.form.submit()"><option value="">All warehouses</option>@foreach ($warehouses as $id => $n)<option value="{{ $id }}" @selected(request('warehouse') == $id)>{{ $n }}</option>@endforeach</select>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control" style="width:145px;min-height:40px" onchange="this.form.submit()">
                <input type="date" name="to" value="{{ request('to') }}" class="form-control" style="width:145px;min-height:40px" onchange="this.form.submit()">
            </form>
        </x-slot:actions>
        @if ($can['delete'])
            <div class="px-4 py-2 d-none" id="bulkBar" style="background:#eef2ff"><span class="small fw-semibold me-2"><span id="bulkCount">0</span> selected</span>
                <button form="bulkForm" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete the selected documents? Stock and payments will be reversed.')">Delete</button></div>
        @endif
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr>
                @if ($can['delete'])<th style="width:34px"><input type="checkbox" class="form-check-input" id="checkAll"></th>@endif
                <th>Date</th><th>Number</th><th>{{ $sale ? 'Customer' : 'Supplier' }}</th><th>Status</th><th class="text-end">Total</th>
                @if ($meta['payments'])<th class="text-end">Paid</th><th class="text-end">Due</th><th>Payment</th>@endif
                <th>By</th><th class="text-end"></th>
            </tr></thead>
            <tbody>
            @forelse ($docs as $d)
                <tr>
                    @if ($can['delete'])<td><input type="checkbox" class="form-check-input row-check" form="bulkForm" name="ids[]" value="{{ $d->id }}"></td>@endif
                    <td class="text-nowrap">{{ $d->date->format('d M Y') }}@if ($d->due_date && in_array($meta['type'], ['invoice', 'quotation']))<div class="small {{ $d->due_date->isPast() && $d->due() > 0 ? 'text-danger' : 'text-body-secondary' }}">{{ $meta['type'] === 'quotation' ? 'Valid' : 'Due' }} {{ $d->due_date->format('d M') }}</div>@endif</td>
                    <td><a href="{{ route($meta['route'].'.show', $d) }}" class="fw-semibold text-decoration-none">{{ $d->number }}</a>
                        @if ($d->is_pos)<x-admin.badge tone="primary" icon="shop">POS</x-admin.badge>@endif
                        @if ($d->reference_no)<div class="small text-body-secondary">Ref {{ $d->reference_no }}</div>@endif</td>
                    <td>{{ $d->partyName() }}@if ($d->salesman)<div class="small text-body-secondary">{{ $d->salesman->name }}</div>@endif</td>
                    <td><x-admin.badge :tone="in_array($d->status, ['final', 'received', 'delivered', 'converted']) ? 'success' : (in_array($d->status, ['cancelled']) ? 'muted' : 'warning')">{{ $meta['statuses'][$d->status] ?? $d->status }}</x-admin.badge></td>
                    <td class="text-end fw-semibold">{{ $money($d->grand_total) }}</td>
                    @if ($meta['payments'])
                        <td class="text-end">{{ $money($d->paid_amount) }}</td>
                        <td class="text-end {{ $d->due() > 0 ? 'text-danger fw-semibold' : '' }}">{{ $money($d->due()) }}</td>
                        <td><x-admin.badge :tone="$tone[$d->payment_status] ?? 'muted'">{{ ucfirst($d->payment_status) }}</x-admin.badge></td>
                    @endif
                    <td class="small">{{ $users[$d->created_by] ?? '—' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route($meta['route'].'.show', $d) }}" class="btn btn-action" title="View"><i class="bi bi-eye"></i></a>
                        <a href="{{ route($meta['route'].'.print', $d) }}" target="_blank" class="btn btn-action" title="Print"><i class="bi bi-printer"></i></a>
                        @if ($can['edit'])<a href="{{ route($meta['route'].'.edit', $d) }}" class="btn btn-action" title="Edit"><i class="bi bi-pencil-square"></i></a>@endif
                        @if ($can['delete'])<x-admin.delete-button :action="route($meta['route'].'.destroy', $d)" :confirm="'Delete '.$d->number.'? Stock and payments will be reversed.'" />@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="11"><x-admin.empty :icon="$meta['icon']" :text="'No '.strtolower($meta['many']).' found.'" /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($docs->hasPages())
            <x-slot:footer>{{ $docs->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const all = document.getElementById('checkAll'), bar = document.getElementById('bulkBar'), cnt = document.getElementById('bulkCount');
        const sync = () => { const n = document.querySelectorAll('.row-check:checked').length; if (bar) { bar.classList.toggle('d-none', !n); cnt.textContent = n; } };
        all?.addEventListener('change', () => { document.querySelectorAll('.row-check').forEach(c => c.checked = all.checked); sync(); });
        document.querySelectorAll('.row-check').forEach(c => c.addEventListener('change', sync));
    })();
</script>
@endpush
