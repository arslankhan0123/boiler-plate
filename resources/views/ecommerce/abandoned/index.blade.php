@extends('layouts.admin.main')
@section('title', 'Abandoned Carts')
@section('subtitle', 'E-commerce')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Abandoned Carts" :subtitle="'Potential value: '.number_format($lostValue, 2)" />
    <x-admin.card flush>
        <div class="table-responsive"><table class="table app-table align-middle">
            <thead><tr><th>Email</th><th class="text-center">Items</th><th class="text-end">Value</th><th>Last activity</th></tr></thead>
            <tbody>
            @forelse ($carts as $c)
                <tr>
                    <td class="small">{{ $c->customer_email ?: 'Guest' }}</td>
                    <td class="text-center">{{ $c->itemCount() }}</td>
                    <td class="text-end">{{ number_format($c->subtotal, 2) }}</td>
                    <td class="small">{{ $c->updated_at?->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="4"><x-admin.empty icon="cart-x" text="No abandoned carts." /></td></tr>
            @endforelse
            </tbody></table></div>
        <x-slot:footer>{{ $carts->links('pagination::bootstrap-5') }}</x-slot:footer>
    </x-admin.card>
</div>
@endsection
