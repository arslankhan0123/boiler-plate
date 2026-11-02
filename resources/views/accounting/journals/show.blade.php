@extends('layouts.admin.main')
@section('title', $journal->reference)
@section('subtitle', 'Journal entry')
@section('content')
<div class="app-content">
    <x-admin.page-header :title="$journal->reference" :subtitle="$journal->date->format('d M Y')" :back="route('accounting.journals.index')">
        @if ($canPost)
            @if (!$journal->isPosted())
                <x-admin.button variant="light" icon="pencil-square" :href="route('accounting.journals.edit', $journal)">Edit</x-admin.button>
                <form method="POST" action="{{ route('accounting.journals.post', $journal) }}">@csrf<x-admin.button icon="check2-circle">Post</x-admin.button></form>
            @else
                <form method="POST" action="{{ route('accounting.journals.unpost', $journal) }}">@csrf<x-admin.button variant="light" icon="arrow-counterclockwise">Unpost</x-admin.button></form>
            @endif
        @endif
    </x-admin.page-header>
    <x-admin.card>
        <div class="d-flex justify-content-between mb-3">
            <div><x-admin.badge :tone="$journal->isPosted() ? 'success' : 'muted'">{{ ucfirst($journal->status) }}</x-admin.badge> <span class="text-body-secondary">{{ $journal->memo }}</span></div>
        </div>
        <div class="table-responsive"><table class="table app-table align-middle">
            <thead><tr><th>Account</th><th>Memo</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
            <tbody>
            @foreach ($journal->lines as $l)
                <tr><td>{{ $l->account->code ?? '' }} — {{ $l->account->name ?? '' }}</td><td class="small">{{ $l->memo }}</td><td class="text-end">{{ $l->debit > 0 ? number_format($l->debit, 2) : '' }}</td><td class="text-end">{{ $l->credit > 0 ? number_format($l->credit, 2) : '' }}</td></tr>
            @endforeach
            </tbody>
            <tfoot><tr class="fw-bold"><td colspan="2" class="text-end">Totals</td><td class="text-end">{{ number_format($journal->totalDebit(), 2) }}</td><td class="text-end">{{ number_format($journal->totalCredit(), 2) }}</td></tr></tfoot>
        </table></div>
    </x-admin.card>
</div>
@endsection
