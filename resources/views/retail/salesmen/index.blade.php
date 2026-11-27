@extends('layouts.admin.main')
@php $money = fn ($v) => number_format((float) $v, 2); @endphp
@section('title', 'Salesmen')
@section('subtitle', 'Sales')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Salesmen" subtitle="Assign a salesman on invoices — they earn the items' seller points and an optional commission %." />
    <div class="app-form-grid">
        <x-admin.card flush :title="'Salesmen ('.$rows->count().')'">
            <div class="table-responsive"><table class="table app-table table-hover">
                <thead><tr><th>Salesman</th><th>Contact</th><th class="text-end">Sales</th><th class="text-end">Earned</th><th class="text-end">Paid + advance</th><th class="text-end">Balance</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse ($rows as $s)
                    @php $st = $stats[$s->id]; @endphp
                    <tr>
                        <td><a href="{{ route('sales.salesmen.show', $s) }}" class="fw-semibold text-decoration-none">{{ $s->name }}</a><div class="small text-body-secondary">{{ $s->code }}{{ $s->commission_rate > 0 ? ' · '.+$s->commission_rate.'% commission' : '' }}</div></td>
                        <td class="small">{{ $s->mobile ?: '—' }}<div class="text-body-secondary">{{ $s->email }}</div></td>
                        <td class="text-end">{{ $money($st['sales']) }}<div class="small text-body-secondary">{{ $st['invoices'] }} invoices</div></td>
                        <td class="text-end">{{ $money($st['earned']) }}</td>
                        <td class="text-end">{{ $money($st['paid'] + $st['advance']) }}</td>
                        <td class="text-end fw-bold {{ $st['balance'] > 0 ? 'text-danger' : '' }}">{{ $money($st['balance']) }}</td>
                        <td><x-admin.badge :tone="$s->is_active ? 'success' : 'muted'">{{ $s->is_active ? 'Active' : 'Inactive' }}</x-admin.badge></td>
                        <td class="text-end text-nowrap"><a href="{{ route('sales.salesmen.show', $s) }}" class="btn btn-action"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('sales.salesmen.index', ['edit' => $s->id]) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route('sales.salesmen.destroy', $s)" confirm="Delete this salesman?" /></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><x-admin.empty icon="person-badge" text="No salesmen yet." /></td></tr>
                @endforelse
                </tbody></table></div>
        </x-admin.card>
        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">{{ $editing ? 'Edit salesman' : 'New salesman' }}</h3>
                <form method="POST" action="{{ $editing ? route('sales.salesmen.update', $editing) : route('sales.salesmen.store') }}">@csrf @if ($editing) @method('PUT') @endif
                    <div class="row g-2">
                        <div class="col-4"><x-admin.input name="code" label="Code" :value="$editing?->code ?? $nextCode" /></div>
                        <div class="col-8"><x-admin.input name="name" label="Name" :value="$editing?->name" required /></div>
                        <div class="col-6"><x-admin.input name="mobile" label="Mobile" :value="$editing?->mobile" optional /></div>
                        <div class="col-6"><x-admin.input name="email" type="email" label="Email" :value="$editing?->email" optional /></div>
                    </div>
                    <x-admin.input name="address" label="Address" :value="$editing?->address" optional />
                    <div class="row g-2">
                        <div class="col-6"><x-admin.input name="commission_rate" type="number" step="any" min="0" max="100" label="Commission %" :value="$editing?->commission_rate ?: ''" optional /></div>
                        <div class="col-6"><x-admin.input name="opening_balance" type="number" step="any" label="Opening balance" :value="$editing?->opening_balance ?: ''" optional /></div>
                    </div>
                    <label class="d-flex gap-2 align-items-center small"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input mt-0" name="is_active" value="1" @checked(old('is_active', $editing?->is_active ?? true))> Active</label>
                    <div class="d-flex gap-2 mt-4"><x-admin.button icon="check2">{{ $editing ? 'Save' : 'Add salesman' }}</x-admin.button>@if ($editing)<x-admin.button variant="light" :href="route('sales.salesmen.index')">Cancel</x-admin.button>@endif</div>
                </form>
            </div></div>
        </div>
    </div>
</div>
@endsection
