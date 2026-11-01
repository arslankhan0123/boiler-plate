@extends('layouts.admin.main')
@section('title', 'Taxes & Currencies')
@section('subtitle', 'Settings')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Taxes & Currencies" subtitle="Tax rates for sales and purchases, and the currencies you trade in." />

    <div class="app-form-grid">
        <div class="d-grid gap-4">
            <x-admin.card flush :title="'Tax rates ('.$taxes->count().')'">
                <div class="table-responsive"><table class="table app-table table-hover">
                    <thead><tr><th>Tax</th><th>Rate</th><th>Type</th><th>Status</th><th class="text-end"></th></tr></thead>
                    <tbody>
                    @forelse ($taxes as $t)
                        <tr>
                            <td class="fw-semibold">{{ $t->name }} @if ($t->is_default)<x-admin.badge tone="primary">Default</x-admin.badge>@endif</td>
                            <td class="fw-bold">{{ rtrim(rtrim(number_format($t->rate, 3), '0'), '.') }}%</td>
                            <td>{{ $t->type === 'inclusive' ? 'Included in price' : 'Added on top' }}</td>
                            <td><x-admin.badge :tone="$t->is_active ? 'success' : 'muted'">{{ $t->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></td>
                            <td class="text-end text-nowrap">@if ($canEdit)
                                <a href="{{ route('settings.taxes', ['edit' => $t->id]) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                                <x-admin.delete-button :action="route('settings.taxes.destroy', $t)" confirm="Remove this tax rate?" />@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-admin.empty icon="percent" text="No tax rates yet (e.g. GST 18%)." /></td></tr>
                    @endforelse
                    </tbody></table></div>
            </x-admin.card>

            <x-admin.card flush :title="'Currencies ('.$currencies->count().')'">
                @if ($canEdit && $available->isNotEmpty())
                    <x-slot:actions>
                        <form method="POST" action="{{ route('settings.currencies.store') }}" class="d-flex gap-2">@csrf
                            <select name="lookup_id" class="form-select" style="width:240px;min-height:40px" required>
                                <option value="">Add a currency…</option>
                                @foreach ($available as $c)<option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>@endforeach
                            </select>
                            <x-admin.button size="sm" icon="plus-lg">Add</x-admin.button>
                        </form>
                    </x-slot:actions>
                @endif
                <div class="table-responsive"><table class="table app-table">
                    <thead><tr><th>Currency</th><th>Symbol</th><th>Status</th><th class="text-end"></th></tr></thead>
                    <tbody>
                    @forelse ($currencies as $c)
                        <tr>
                            <td><span class="fw-semibold">{{ $c->code }}</span> <span class="text-body-secondary">{{ $c->name }}</span>
                                @if ($c->is_default)<x-admin.badge tone="primary" icon="star-fill">Default</x-admin.badge>@endif</td>
                            <td>{{ $c->symbol ?: '—' }}</td>
                            <td><x-admin.badge :tone="$c->is_active ? 'success' : 'muted'">{{ $c->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></td>
                            <td class="text-end text-nowrap">@if ($canEdit)
                                @unless ($c->is_default)
                                    <form method="POST" action="{{ route('settings.currencies.update', $c) }}" class="d-inline">@csrf @method('PATCH')
                                        <input type="hidden" name="make_default" value="1"><button class="btn btn-sm btn-light">Make default</button></form>
                                    <form method="POST" action="{{ route('settings.currencies.update', $c) }}" class="d-inline">@csrf @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $c->is_active ? 0 : 1 }}"><button class="btn btn-sm btn-light">{{ $c->is_active ? 'Disable' : 'Enable' }}</button></form>
                                @endunless
                            @endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-admin.empty icon="currency-exchange" text="No currencies yet — add PKR to start." /></td></tr>
                    @endforelse
                    </tbody></table></div>
            </x-admin.card>
        </div>

        @if ($canEdit)
            <div class="app-aside">
                <div class="app-card"><div class="app-card__body">
                    <h3 class="app-aside-title">{{ $editing ? 'Edit tax rate' : 'New tax rate' }}</h3>
                    <form method="POST" action="{{ $editing ? route('settings.taxes.update', $editing) : route('settings.taxes.store') }}">@csrf @if ($editing) @method('PUT') @endif
                        <x-admin.input name="name" label="Name" :value="$editing?->name" placeholder="e.g. GST" required />
                        <x-admin.input name="rate" type="number" step="0.001" min="0" max="100" label="Rate (%)" :value="$editing?->rate" placeholder="18" required />
                        <x-admin.select name="type" label="Type" :options="['exclusive' => 'Added on top of the price', 'inclusive' => 'Included in the price']" :value="$editing?->type ?? 'exclusive'" required />
                        <div class="d-grid gap-2 mt-3 small">
                            <label class="d-flex gap-2 align-items-center"><input type="checkbox" class="form-check-input mt-0" name="is_default" value="1" @checked(old('is_default', $editing?->is_default))> Default tax</label>
                            <label class="d-flex gap-2 align-items-center"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input mt-0" name="is_active" value="1" @checked(old('is_active', $editing?->is_active ?? true))> Active</label>
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <x-admin.button icon="check2">{{ $editing ? 'Save' : 'Add Tax' }}</x-admin.button>
                            @if ($editing)<x-admin.button variant="light" :href="route('settings.taxes')">Cancel</x-admin.button>@endif
                        </div>
                    </form>
                </div></div>
            </div>
        @endif
    </div>
</div>
@endsection
