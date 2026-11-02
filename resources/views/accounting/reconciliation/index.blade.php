@extends('layouts.admin.main')
@section('title', 'Reconciliation')
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Bank Reconciliation" :subtitle="$selected ? $selected->name : null" />
    <div class="row g-3 mb-3">
        <div class="col-md-4"><x-admin.stat label="Book balance" :value="number_format($bookBalance, 2)" icon="journal-check" tone="primary" /></div>
        <div class="col-md-4"><x-admin.stat label="Cleared balance" :value="number_format($clearedBalance, 2)" icon="check2-circle" tone="success" /></div>
        <div class="col-md-4"><x-admin.stat label="Difference" :value="number_format($bookBalance - $clearedBalance, 2)" icon="dash-circle" tone="warning" /></div>
    </div>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET"><select name="account_id" class="form-select" style="width:240px;min-height:40px" onchange="this.form.submit()">@foreach ($banks as $b)<option value="{{ $b->id }}" @selected(optional($selected)->id === $b->id)>{{ $b->code }} — {{ $b->name }}</option>@endforeach</select></form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table align-middle">
            <thead><tr><th>Date</th><th>Reference</th><th>Memo</th><th class="text-end">In</th><th class="text-end">Out</th><th class="text-center">Cleared</th></tr></thead>
            <tbody>
            @forelse ($lines as $l)
                <tr class="{{ $l->reconciled ? 'table-success' : '' }}">
                    <td class="small">{{ \Illuminate\Support\Carbon::parse($l->date)->format('d M Y') }}</td><td class="small">{{ $l->reference }}</td><td class="small">{{ $l->memo }}</td>
                    <td class="text-end">{{ $l->debit > 0 ? number_format($l->debit, 2) : '' }}</td><td class="text-end">{{ $l->credit > 0 ? number_format($l->credit, 2) : '' }}</td>
                    <td class="text-center"><form method="POST" action="{{ route('accounting.reconciliation.toggle', $l->id) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-link p-0"><i class="bi {{ $l->reconciled ? 'bi-check-circle-fill text-success' : 'bi-circle' }}"></i></button></form></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-admin.empty icon="bank" text="No posted lines for this account." /></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </x-admin.card>
</div>
@endsection
