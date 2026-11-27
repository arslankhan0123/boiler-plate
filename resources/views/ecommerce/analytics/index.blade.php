@extends('layouts.admin.main')
@section('title', 'Store Analytics')
@section('subtitle', 'E-commerce')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Store Analytics" subtitle="How your store is performing" />
    <div class="row g-3 mb-4">
        <div class="col-md-3"><x-admin.stat label="Orders" :value="$orderCount" icon="cart-check" tone="primary" /></div>
        <div class="col-md-3"><x-admin.stat label="Revenue" :value="number_format($revenue, 0)" icon="cash-stack" tone="success" /></div>
        <div class="col-md-3"><x-admin.stat label="Avg order" :value="number_format($avgOrder, 0)" icon="graph-up" tone="info" /></div>
        <div class="col-md-3"><x-admin.stat label="Published" :value="$published" icon="bag-check" tone="warning" /></div>
    </div>
    <div class="row g-4">
        <div class="col-lg-6"><x-admin.card title="Orders by status">
            <table class="table app-table align-middle mb-0"><tbody>
                @forelse ($byStatus as $s)
                    <tr><td>{{ \App\Models\Ecommerce\Order::STATUSES[$s->status] ?? $s->status }}</td><td class="text-center">{{ $s->n }}</td><td class="text-end">{{ number_format($s->total, 2) }}</td></tr>
                @empty<tr><td class="text-body-secondary small">No orders yet.</td></tr>@endforelse
            </tbody></table>
        </x-admin.card></div>
        <div class="col-lg-6"><x-admin.card title="Top products">
            <table class="table app-table align-middle mb-0"><tbody>
                @forelse ($topProducts as $p)
                    <tr><td>{{ $p->name }}</td><td class="text-center">{{ (int) $p->qty }} sold</td><td class="text-end">{{ number_format($p->total, 2) }}</td></tr>
                @empty<tr><td class="text-body-secondary small">No sales yet.</td></tr>@endforelse
            </tbody></table>
        </x-admin.card></div>
    </div>
</div>
@endsection
