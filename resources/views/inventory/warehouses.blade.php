@extends('layouts.admin.main')
@section('title', 'Warehouses')
@section('subtitle', 'Inventory')
@php $money = fn ($v) => \App\Support\Settings::get('default_currency', 'PKR').' '.number_format((float) $v, 2); @endphp

@section('content')
<div class="app-content">
    <x-admin.page-header title="Warehouses" subtitle="Stores, shops and godowns. Every item keeps its stock per warehouse." />
    <div class="app-form-grid">
        <x-admin.card flush :title="'Warehouses ('.$warehouses->count().')'">
            <div class="table-responsive"><table class="table app-table table-hover">
                <thead><tr><th>Warehouse</th><th>Contact</th><th>Items in stock</th><th>Stock value</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                @foreach ($warehouses as $w)
                    @php $sum = $summary[$w->id] ?? null; @endphp
                    <tr>
                        <td><a href="{{ route('inventory.warehouses.show', $w) }}" class="fw-semibold text-decoration-none">{{ $w->name }}</a>
                            @if ($w->is_default)<x-admin.badge tone="primary" icon="star-fill">Default</x-admin.badge>@endif
                            <div class="small text-body-secondary">{{ \App\Models\Inventory\Warehouse::TYPES[$w->type] ?? $w->type }}{{ $w->city ? ' · '.$w->city : '' }}</div></td>
                        <td class="small">{{ $w->contact_person ?: '—' }}<div class="text-body-secondary">{{ $w->mobile }}</div></td>
                        <td>{{ (int) ($sum->items ?? 0) }} <span class="small text-body-secondary">({{ \App\Support\Inventory\StockService::fmt((float) ($sum->qty ?? 0)) }} qty)</span></td>
                        <td class="fw-semibold">{{ $money($sum->value ?? 0) }}</td>
                        <td><x-admin.badge :tone="$w->is_active ? 'success' : 'muted'">{{ $w->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('inventory.warehouses.show', $w) }}" class="btn btn-action" title="Stock"><i class="bi bi-box-seam"></i></a>
                            @if ($canEdit)
                                <a href="{{ route('inventory.warehouses.index', ['edit' => $w->id]) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                                @unless ($w->is_default)<x-admin.delete-button :action="route('inventory.warehouses.destroy', $w)" confirm="Delete this warehouse?" />@endunless
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody></table></div>
        </x-admin.card>
        @if ($canEdit)
            <div class="app-aside">
                <div class="app-card"><div class="app-card__body">
                    <h3 class="app-aside-title">{{ $editing ? 'Edit warehouse' : 'New warehouse' }}</h3>
                    <form method="POST" action="{{ $editing ? route('inventory.warehouses.update', $editing) : route('inventory.warehouses.store') }}">@csrf @if ($editing) @method('PUT') @endif
                        <x-admin.input name="name" label="Name" :value="$editing?->name" placeholder="e.g. Shop — Gulberg" required />
                        <x-admin.select name="type" label="Type" :options="\App\Models\Inventory\Warehouse::TYPES" :value="$editing?->type ?? 'warehouse'" required />
                        <div class="row g-3 mt-0">
                            <div class="col-6"><x-admin.input name="contact_person" label="Contact person" optional :value="$editing?->contact_person" /></div>
                            <div class="col-6"><x-admin.input name="mobile" label="Mobile" optional :value="$editing?->mobile" /></div>
                            <div class="col-6"><x-admin.input name="phone" label="Phone" optional :value="$editing?->phone" /></div>
                            <div class="col-6"><x-admin.input name="email" type="email" label="Email" optional :value="$editing?->email" /></div>
                            <div class="col-6"><x-admin.input name="city" label="City" optional :value="$editing?->city" /></div>
                            <div class="col-6"><x-admin.input name="area" label="Area" optional :value="$editing?->area" /></div>
                        </div>
                        <x-admin.input name="address" label="Address" optional :value="$editing?->address" />
                        <div class="d-grid gap-2 small mt-2">
                            <label class="d-flex gap-2 align-items-center"><input type="checkbox" class="form-check-input mt-0" name="is_default" value="1" @checked(old('is_default', $editing?->is_default))> Default warehouse (used by POS & new documents)</label>
                            <label class="d-flex gap-2 align-items-center"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input mt-0" name="is_active" value="1" @checked(old('is_active', $editing?->is_active ?? true))> Active</label>
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <x-admin.button icon="check2">{{ $editing ? 'Save' : 'Add Warehouse' }}</x-admin.button>
                            @if ($editing)<x-admin.button variant="light" :href="route('inventory.warehouses.index')">Cancel</x-admin.button>@endif
                        </div>
                    </form>
                </div></div>
            </div>
        @endif
    </div>
</div>
@endsection
