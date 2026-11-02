@extends('layouts.admin.main')
@section('title', 'Discount Coupons')
@section('subtitle', 'Sales')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Discount Coupons" subtitle="Codes that give a % or fixed discount on a sale (invoice or POS).">
        <x-admin.button variant="light" icon="person-badge" :href="route('sales.coupons.customer')">Customer coupons</x-admin.button>
    </x-admin.page-header>
    <div class="app-form-grid">
        <x-admin.card flush :title="'Coupons ('.$coupons->count().')'">
            <div class="table-responsive"><table class="table app-table table-hover">
                <thead><tr><th>Code</th><th>Discount</th><th>Min. bill</th><th>Expiry</th><th>Used on sales</th><th>Issued to customers</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse ($coupons as $c)
                    <tr>
                        <td><span class="fw-bold">{{ $c->code }}</span><div class="small text-body-secondary">{{ $c->name }}</div></td>
                        <td class="fw-semibold">{{ $c->label() }}</td>
                        <td class="small">{{ $c->min_amount > 0 ? number_format($c->min_amount, 2) : '—' }}</td>
                        <td class="small {{ $c->isExpired() ? 'text-danger' : '' }}">{{ $c->expire_date?->format('d M Y') ?? 'Never' }}</td>
                        <td class="small">{{ $usage[$c->id]->n ?? 0 }} × <span class="text-body-secondary">{{ number_format($usage[$c->id]->amt ?? 0, 2) }}</span></td>
                        <td class="small">{{ $c->customer_coupons_count }} ({{ $c->used_count }} used)</td>
                        <td><x-admin.badge :tone="$c->is_active && ! $c->isExpired() ? 'success' : 'muted'">{{ $c->isExpired() ? 'Expired' : ($c->is_active ? 'Active' : 'Inactive') }}</x-admin.badge></td>
                        <td class="text-end text-nowrap"><a href="{{ route('sales.coupons.index', ['edit' => $c->id]) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route('sales.coupons.destroy', $c)" confirm="Delete this coupon?" /></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-admin.empty icon="ticket-perforated" text="No coupons yet." /></td></tr>
                @endforelse
                </tbody></table></div>
        </x-admin.card>
        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">{{ $editing ? 'Edit coupon' : 'New coupon' }}</h3>
                <form method="POST" action="{{ $editing ? route('sales.coupons.update', $editing) : route('sales.coupons.store') }}">@csrf @if ($editing) @method('PUT') @endif
                    <x-admin.input name="code" label="Code" :value="$editing?->code" placeholder="EID25" required />
                    <x-admin.input name="name" label="Name" :value="$editing?->name" placeholder="Eid sale" required />
                    <div class="row g-2">
                        <div class="col-6"><x-admin.select name="type" label="Type" :options="['percentage' => 'Percentage %', 'fixed' => 'Fixed amount']" :value="$editing?->type ?? 'percentage'" required /></div>
                        <div class="col-6"><x-admin.input name="value" type="number" step="any" min="0" label="Value" :value="$editing?->value" required /></div>
                        <div class="col-6"><x-admin.input name="min_amount" type="number" step="any" min="0" label="Min. bill" :value="$editing?->min_amount ?: ''" optional /></div>
                        <div class="col-6"><x-admin.input name="expire_date" type="date" label="Expires" :value="$editing?->expire_date?->toDateString()" optional /></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Description <span class="opt">(optional)</span></label><textarea name="description" rows="2" class="form-control">{{ old('description', $editing?->description) }}</textarea></div>
                    <label class="d-flex gap-2 align-items-center small"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input mt-0" name="is_active" value="1" @checked(old('is_active', $editing?->is_active ?? true))> Active</label>
                    <div class="d-flex gap-2 mt-4"><x-admin.button icon="check2">{{ $editing ? 'Save' : 'Create coupon' }}</x-admin.button>@if ($editing)<x-admin.button variant="light" :href="route('sales.coupons.index')">Cancel</x-admin.button>@endif</div>
                </form>
            </div></div>
        </div>
    </div>
</div>
@endsection
