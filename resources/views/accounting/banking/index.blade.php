@extends('layouts.admin.main')
@section('title', 'Bank & Cash')
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Bank & Cash" :subtitle="'Total on hand: '.number_format($totalCash, 2)" />
    <div class="row g-3 mb-3">
        @forelse ($banks as $b)
            <div class="col-md-4"><a href="{{ route('accounting.banking.index', ['account_id' => $b->id]) }}" class="app-card d-block text-decoration-none {{ optional($selected)->id === $b->id ? 'border-primary' : '' }}">
                <div class="app-card__body">
                    <div class="small text-body-secondary">{{ $b->code }} · {{ $b->name }}</div>
                    <div class="h4 mb-0">{{ number_format($balances[$b->id] ?? 0, 2) }}</div>
                </div></a></div>
        @empty
            <div class="col-12"><x-admin.empty icon="bank" text="No bank/cash accounts. Mark an account as 'Bank / cash' in the Chart of Accounts." /></div>
        @endforelse
    </div>
    @if ($selected)
        <x-admin.card :title="'Register — '.$selected->name">
            <div class="table-responsive"><table class="table app-table align-middle">
                <thead><tr><th>Date</th><th>Reference</th><th>Memo</th><th class="text-end">In</th><th class="text-end">Out</th><th class="text-center">Cleared</th></tr></thead>
                <tbody>
                @forelse ($register as $t)
                    <tr><td class="small">{{ \Illuminate\Support\Carbon::parse($t->date)->format('d M Y') }}</td><td class="small">{{ $t->reference }}</td><td class="small">{{ $t->memo }}</td>
                    <td class="text-end">{{ $t->debit > 0 ? number_format($t->debit, 2) : '' }}</td><td class="text-end">{{ $t->credit > 0 ? number_format($t->credit, 2) : '' }}</td>
                    <td class="text-center">@if ($t->reconciled)<i class="bi bi-check-circle-fill text-success"></i>@endif</td></tr>
                @empty
                    <tr><td colspan="6"><x-admin.empty icon="inbox" text="No posted transactions yet." /></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </x-admin.card>
    @endif
</div>
@endsection
