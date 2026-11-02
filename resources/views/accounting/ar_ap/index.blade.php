@extends('layouts.admin.main')
@section('title', $title)
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$title" :subtitle="'Total: '.number_format($total, 2)" />
    <x-admin.card flush>
        <div class="table-responsive"><table class="table app-table align-middle">
            <thead><tr><th>Party</th><th class="text-end">Balance</th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                <tr><td><i class="bi bi-{{ $icon }} me-2 text-body-secondary"></i>{{ $r['name'] }}</td><td class="text-end fw-semibold">{{ number_format($r['balance'], 2) }}</td></tr>
            @empty
                <tr><td colspan="2"><x-admin.empty icon="inbox" text="Nothing outstanding. Post journal entries against a control account (with a party) to see balances here." /></td></tr>
            @endforelse
            </tbody>
            @if (count($rows))<tfoot><tr class="fw-bold"><td class="text-end">Total</td><td class="text-end">{{ number_format($total, 2) }}</td></tr></tfoot>@endif
        </table></div>
    </x-admin.card>
</div>
@endsection
