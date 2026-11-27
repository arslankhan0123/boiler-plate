@extends('layouts.admin.main')
@section('title', 'Customer Accounts')
@section('subtitle', 'E-commerce')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Customer Accounts" subtitle="Shoppers who placed online orders" />
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2"><input name="q" value="{{ request('q') }}" class="form-control" style="width:240px;min-height:40px" placeholder="Name or email…"></form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table align-middle">
            <thead><tr><th>Customer</th><th>Contact</th><th class="text-center">Orders</th><th class="text-end">Spent</th><th>Last order</th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td class="fw-semibold">{{ $r->customer_name }}</td>
                    <td class="small">{{ $r->customer_phone ?: '—' }}<div class="text-body-secondary">{{ $r->customer_email }}</div></td>
                    <td class="text-center">{{ $r->orders }}</td>
                    <td class="text-end fw-semibold">{{ number_format($r->spent, 2) }}</td>
                    <td class="small">{{ $r->last_order ? \Illuminate\Support\Carbon::parse($r->last_order)->format('d M Y') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5"><x-admin.empty icon="people" text="No store customers yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        <x-slot:footer>{{ $rows->links('pagination::bootstrap-5') }}</x-slot:footer>
    </x-admin.card>
</div>
@endsection
