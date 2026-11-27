@extends('layouts.admin.main')
@section('title', 'Customer Coupons')
@section('subtitle', 'Sales · Coupons')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Customer Coupons" subtitle="One-time codes issued to specific customers. Each code works only for its customer, once." :back="route('sales.coupons.index')" />
    <form method="GET" action="{{ route('sales.coupons.print') }}" target="_blank" id="printForm"></form>
    <div class="app-form-grid">
        <x-admin.card flush :title="'Issued coupons ('.$rows->total().')'">
            <x-slot:actions>
                <form method="GET" class="d-flex flex-wrap gap-2">
                    <input name="q" value="{{ request('q') }}" class="form-control" style="width:180px;min-height:40px" placeholder="Code or customer…">
                    <select name="coupon" class="form-select" style="width:150px;min-height:40px" onchange="this.form.submit()"><option value="">All coupons</option>@foreach ($coupons as $c)<option value="{{ $c->id }}" @selected(request('coupon') == $c->id)>{{ $c->code }}</option>@endforeach</select>
                    <select name="state" class="form-select" style="width:120px;min-height:40px" onchange="this.form.submit()"><option value="">Any</option><option value="unused" @selected(request('state') === 'unused')>Unused</option><option value="used" @selected(request('state') === 'used')>Used</option></select>
                    <button form="printForm" class="btn btn-light" style="min-height:40px"><i class="bi bi-printer"></i> Print selected</button>
                </form>
            </x-slot:actions>
            <div class="table-responsive"><table class="table app-table table-hover align-middle">
                <thead><tr><th style="width:34px"><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.cc').forEach(c => c.checked = this.checked)"></th><th>Code</th><th>Customer</th><th>Coupon</th><th>Expiry</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse ($rows as $r)
                    <tr>
                        <td><input type="checkbox" class="form-check-input cc" form="printForm" name="ids[]" value="{{ $r->id }}"></td>
                        <td class="fw-bold">{{ $r->code }}</td><td>{{ $r->customer->company_name ?? '—' }}</td>
                        <td class="small">{{ $r->coupon->name ?? '' }} <b>{{ $r->coupon?->label() }}</b></td>
                        <td class="small {{ $r->expire_date && $r->expire_date->isPast() ? 'text-danger' : '' }}">{{ $r->expire_date?->format('d M Y') ?? 'Never' }}</td>
                        <td>@if ($r->used_document_id)<x-admin.badge tone="muted">Used {{ $r->used_at?->format('d M') }}</x-admin.badge>@elseif (! $r->is_active)<x-admin.badge tone="muted">Inactive</x-admin.badge>@else<x-admin.badge tone="success">Available</x-admin.badge>@endif</td>
                        <td class="text-end text-nowrap">@unless ($r->used_document_id)
                            <form method="POST" action="{{ route('sales.coupons.customer.toggle', $r) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-light">{{ $r->is_active ? 'Disable' : 'Enable' }}</button></form>
                            <x-admin.delete-button :action="route('sales.coupons.customer.destroy', $r)" confirm="Delete this coupon?" />@endunless</td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-admin.empty icon="ticket" text="No customer coupons yet." /></td></tr>
                @endforelse
                </tbody></table></div>
            @if ($rows->hasPages())
                <x-slot:footer>{{ $rows->links('pagination::bootstrap-5') }}</x-slot:footer>
            @endif
        </x-admin.card>
        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Generate coupons</h3>
                @if ($coupons->isEmpty())<p class="small text-body-secondary">Create a <a href="{{ route('sales.coupons.index') }}">discount coupon</a> first.</p>@else
                <form method="POST" action="{{ route('sales.coupons.generate') }}">@csrf
                    <x-admin.select name="coupon_id" label="Coupon" :options="$coupons->mapWithKeys(fn ($c) => [$c->id => $c->code.' — '.$c->label()])" required />
                    <label class="form-label">Customers</label>
                    <select name="customer_ids[]" multiple class="form-select" size="8">@foreach ($customers as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select>
                    <div class="form-text">Ctrl / Cmd + click to pick several.</div>
                    <label class="d-flex gap-2 align-items-center small mt-2"><input type="checkbox" class="form-check-input mt-0" name="all" value="1"> All active customers</label>
                    <x-admin.input name="expire_date" type="date" label="Expiry (optional)" class="mt-2" />
                    <x-admin.button icon="magic" class="w-100">Generate</x-admin.button>
                </form>@endif
            </div></div>
        </div>
    </div>
</div>
@endsection
