@extends('layouts.admin.main')
@section('title', 'General Ledger')
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header title="General Ledger" :subtitle="$account ? $account->code.' — '.$account->name : null" />
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2 flex-wrap">
                <select name="account_id" class="form-select" style="width:240px;min-height:40px" onchange="this.form.submit()">@foreach ($accounts as $id => $n)<option value="{{ $id }}" @selected((string)optional($account)->id===(string)$id)>{{ $n }}</option>@endforeach</select>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control" style="width:150px;min-height:40px">
                <input type="date" name="to" value="{{ request('to') }}" class="form-control" style="width:150px;min-height:40px">
                <button class="btn btn-light">Filter</button>
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table align-middle">
            <thead><tr><th>Date</th><th>Reference</th><th>Memo</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td class="small">{{ \Illuminate\Support\Carbon::parse($r['row']->date)->format('d M Y') }}</td>
                    <td class="small">{{ $r['row']->reference }}</td>
                    <td class="small">{{ $r['row']->memo }}</td>
                    <td class="text-end">{{ $r['row']->debit > 0 ? number_format($r['row']->debit, 2) : '' }}</td>
                    <td class="text-end">{{ $r['row']->credit > 0 ? number_format($r['row']->credit, 2) : '' }}</td>
                    <td class="text-end fw-semibold">{{ number_format($r['balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><x-admin.empty icon="journal-text" text="No posted entries for this account." /></td></tr>
            @endforelse
            </tbody>
            <tfoot><tr class="fw-bold"><td colspan="5" class="text-end">Closing balance</td><td class="text-end">{{ number_format($closing, 2) }}</td></tr></tfoot>
        </table></div>
    </x-admin.card>
</div>
@endsection
