@extends('layouts.admin.main')
@section('title', 'Shipping')
@section('subtitle', 'E-commerce')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Shipping & Delivery" subtitle="Methods and rates shown at checkout" />
    <div class="row g-4">
        <div class="col-lg-8"><x-admin.card flush>
            <div class="table-responsive"><table class="table app-table align-middle">
                <thead><tr><th>Method</th><th class="text-end">Rate</th><th class="text-end">Free over</th><th>Status</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse ($methods as $m)
                    <tr>
                        <td class="fw-semibold">{{ $m->name }}</td><td class="text-end">{{ number_format($m->rate, 2) }}</td>
                        <td class="text-end">{{ $m->free_over !== null ? number_format($m->free_over, 0) : '—' }}</td>
                        <td><x-admin.badge :tone="$m->is_active ? 'success' : 'muted'">{{ $m->is_active ? 'Active' : 'Off' }}</x-admin.badge></td>
                        <td class="text-end"><x-admin.delete-button :action="route('ecommerce.shipping.destroy', $m)" :confirm="'Delete '.$m->name.'?'" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-admin.empty icon="truck" text="No shipping methods." /></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </x-admin.card></div>
        <div class="col-lg-4"><x-admin.card title="New method">
            <form method="POST" action="{{ route('ecommerce.shipping.store') }}">@csrf
                <x-admin.input name="name" label="Name" required />
                <x-admin.input name="rate" label="Rate" type="number" step="any" required />
                <x-admin.input name="free_over" label="Free over (order value)" type="number" step="any" optional />
                <label class="form-check mt-1"><input type="checkbox" name="is_active" value="1" class="form-check-input" checked> <span class="form-check-label">Active</span></label>
                <div class="d-grid mt-2"><x-admin.button icon="plus-lg">Add method</x-admin.button></div>
            </form>
        </x-admin.card></div>
    </div>
</div>
@endsection
