@extends('layouts.admin.main')
@section('title', 'Held Invoices')
@section('subtitle', 'POS')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Held Invoices" subtitle="Carts put on hold at the POS. Resume one from the POS screen (Held button / F9)." >
        @can('pos.sell')<x-admin.button icon="shop" :href="route('pos')">Open POS</x-admin.button>@endcan
    </x-admin.page-header>
    <x-admin.card flush>
        <div class="table-responsive"><table class="table app-table table-hover">
            <thead><tr><th>Held at</th><th>Number</th><th>Reference</th><th>Customer</th><th>Items</th><th class="text-end">Total</th></tr></thead>
            <tbody>
            @forelse ($rows as $d)
                <tr><td class="small">{{ $d->created_at?->format('d M Y h:i A') }}</td><td class="fw-semibold">{{ $d->number }}</td><td>{{ $d->reference_no ?: '—' }}</td>
                    <td>{{ $d->partyName() }}</td><td>{{ $d->items_count }}</td><td class="text-end fw-semibold">{{ number_format($d->grand_total, 2) }}</td></tr>
            @empty
                <tr><td colspan="6"><x-admin.empty icon="pause-circle" text="Nothing on hold." /></td></tr>
            @endforelse
            </tbody></table></div>
    </x-admin.card>
</div>
@endsection
