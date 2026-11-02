@extends('layouts.admin.main')
@section('title', 'Tax Management')
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Tax Management" :subtitle="'Net tax liability: '.number_format($totalLiability, 2)" />
    <div class="row g-4">
        <div class="col-lg-6"><x-admin.card title="Tax rates">
            <div class="table-responsive"><table class="table app-table align-middle mb-0">
                <thead><tr><th>Name</th><th class="text-end">Rate</th><th>Type</th></tr></thead>
                <tbody>
                @forelse ($rates as $r)
                    <tr><td>{{ $r->name }} @if ($r->is_default)<x-admin.badge tone="primary">Default</x-admin.badge>@endif</td><td class="text-end">{{ rtrim(rtrim(number_format($r->rate, 3), '0'), '.') }}%</td><td class="small">{{ ucfirst($r->type) }}</td></tr>
                @empty
                    <tr><td colspan="3"><x-admin.empty icon="percent" text="No tax rates. Add them in Settings → Taxes." /></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </x-admin.card></div>
        <div class="col-lg-6"><x-admin.card title="Tax liability accounts">
            <div class="table-responsive"><table class="table app-table align-middle mb-0">
                <thead><tr><th>Account</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                @forelse ($taxAccounts as $a)
                    <tr><td>{{ $a->code }} — {{ $a->name }}</td><td class="text-end fw-semibold">{{ number_format($balances[$a->id] ?? 0, 2) }}</td></tr>
                @empty
                    <tr><td colspan="2"><x-admin.empty icon="receipt" text="No tax accounts found." /></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </x-admin.card></div>
    </div>
</div>
@endsection
