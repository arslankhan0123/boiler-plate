@extends('layouts.admin.main')
@section('title', 'Promotions')
@section('subtitle', 'E-commerce')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Coupons & Promotions">
        <x-admin.button icon="plus-lg" :href="route('ecommerce.promotions.create')">New coupon</x-admin.button>
    </x-admin.page-header>
    <x-admin.card flush>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Code</th><th>Discount</th><th class="text-end">Min order</th><th>Validity</th><th class="text-center">Used</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($coupons as $c)
                <tr>
                    <td class="fw-semibold">{{ $c->code }}</td>
                    <td>{{ $c->type === 'percent' ? rtrim(rtrim(number_format($c->value,2),'0'),'.').'%' : number_format($c->value,2) }}</td>
                    <td class="text-end">{{ $c->min_order > 0 ? number_format($c->min_order, 0) : '—' }}</td>
                    <td class="small">{{ $c->starts_at?->format('d M') ?? 'Any' }} – {{ $c->ends_at?->format('d M Y') ?? 'Any' }}</td>
                    <td class="text-center small">{{ $c->used }}{{ $c->usage_limit ? '/'.$c->usage_limit : '' }}</td>
                    <td><x-admin.badge :tone="$c->is_active ? 'success' : 'muted'">{{ $c->is_active ? 'Active' : 'Off' }}</x-admin.badge></td>
                    <td class="text-end text-nowrap"><a href="{{ route('ecommerce.promotions.edit', $c) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a><x-admin.delete-button :action="route('ecommerce.promotions.destroy', $c)" :confirm="'Delete '.$c->code.'?'" /></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-admin.empty icon="ticket-perforated" text="No coupons yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        <x-slot:footer>{{ $coupons->links('pagination::bootstrap-5') }}</x-slot:footer>
    </x-admin.card>
</div>
@endsection
