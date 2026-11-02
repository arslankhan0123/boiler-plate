@extends('layouts.admin.main')
@php $sale = $side === 'sale'; $money = fn ($v) => number_format((float) $v, 2); $cur = \App\Support\Settings::get('default_currency', 'PKR'); @endphp
@section('title', $sale ? 'Sales Payments' : 'Supplier Payments')
@section('subtitle', $sale ? 'Sales' : 'Purchase')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$sale ? 'Sales Payments' : 'Supplier Payments'" :subtitle="$sale ? 'Money received from customers, advances and refunds — with cheque tracking.' : 'Money paid to suppliers and refunds received.'" />
    <div class="row g-4 mb-4">
        <div class="col-md-4"><x-admin.stat label="Received (in)" :value="$cur.' '.$money($totals->i)" icon="arrow-down-circle" tone="success" /></div>
        <div class="col-md-4"><x-admin.stat label="Paid out" :value="$cur.' '.$money($totals->o)" icon="arrow-up-circle" tone="danger" /></div>
        <div class="col-md-4"><x-admin.stat label="Net" :value="$cur.' '.$money($totals->i - $totals->o)" icon="cash-stack" /></div>
    </div>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:190px;min-height:40px" placeholder="Receipt, invoice, cheque…">
                <select name="method" class="form-select" style="width:140px;min-height:40px" onchange="this.form.submit()"><option value="">Any method</option>@foreach ($methods as $m)<option @selected(request('method') === $m)>{{ $m }}</option>@endforeach</select>
                <select name="kind" class="form-select" style="width:140px;min-height:40px" onchange="this.form.submit()"><option value="">Any type</option>@foreach (\App\Models\Retail\Payment::KINDS as $k => $l)<option value="{{ $k }}" @selected(request('kind') === $k)>{{ $l }}</option>@endforeach</select>
                <select name="cheque_status" class="form-select" style="width:150px;min-height:40px" onchange="this.form.submit()"><option value="">Cheques: all</option>@foreach (['pending', 'cleared', 'bounced'] as $s)<option value="{{ $s }}" @selected(request('cheque_status') === $s)>Cheque {{ $s }}</option>@endforeach</select>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control" style="width:145px;min-height:40px" onchange="this.form.submit()">
                <input type="date" name="to" value="{{ request('to') }}" class="form-control" style="width:145px;min-height:40px" onchange="this.form.submit()">
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Date</th><th>Receipt</th><th>{{ $sale ? 'Customer' : 'Supplier' }}</th><th>Type</th><th>Against</th><th>Method</th><th>Cheque</th><th class="text-end">In</th><th class="text-end">Out</th><th>By</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($rows as $p)
                <tr>
                    <td class="small text-nowrap">{{ $p->date->format('d M Y') }}</td>
                    <td><a href="{{ route('payments.receipt', $p) }}" target="_blank" class="fw-semibold text-decoration-none">{{ $p->code }}</a></td>
                    <td>{{ $p->party->company_name ?? '—' }}</td>
                    <td><x-admin.badge :tone="$p->kind === 'advance' ? 'success' : ($p->kind === 'refund' ? 'warning' : 'primary')">{{ \App\Models\Retail\Payment::KINDS[$p->kind] ?? $p->kind }}</x-admin.badge></td>
                    <td class="small">@if ($p->document)<a href="{{ route(\App\Models\Retail\Document::TYPES[$p->document->type]['route'].'.show', $p->document) }}">{{ $p->document->number }}</a>@else — @endif</td>
                    <td class="small">{{ $p->method }}</td>
                    <td class="small">@if ($p->cheque_no){{ $p->cheque_no }} <span class="text-body-secondary">{{ $p->cheque_date?->format('d M') }}</span>
                        <form method="POST" action="{{ route('payments.cheque', $p) }}">@csrf @method('PATCH')<select name="cheque_status" class="form-select form-select-sm" style="width:110px" onchange="this.form.submit()">@foreach (['pending', 'cleared', 'bounced'] as $s)<option value="{{ $s }}" @selected($p->cheque_status === $s)>{{ ucfirst($s) }}</option>@endforeach</select></form>@else — @endif</td>
                    <td class="text-end text-success fw-semibold">{{ $p->direction === 'in' ? $money($p->amount) : '' }}</td>
                    <td class="text-end text-danger fw-semibold">{{ $p->direction === 'out' ? $money($p->amount) : '' }}</td>
                    <td class="small">{{ $users[$p->created_by] ?? '—' }}</td>
                    <td class="text-end text-nowrap"><a href="{{ route('payments.receipt', $p) }}" target="_blank" class="btn btn-action"><i class="bi bi-printer"></i></a>
                        <x-admin.delete-button :action="route('payments.destroy', $p)" confirm="Delete this payment?" /></td>
                </tr>
            @empty
                <tr><td colspan="11"><x-admin.empty icon="cash" text="No payments found." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($rows->hasPages())
            <x-slot:footer>{{ $rows->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
