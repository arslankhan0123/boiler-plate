@extends('layouts.admin.main')
@section('title', 'Expense & Receipt')
@section('subtitle', 'Accounting')
@section('content')
<div class="app-content">
    <x-admin.page-header title="Expense & Receipt Vouchers" subtitle="Everyday money out / money in">
        @if ($canPost)
            <x-admin.button variant="light" icon="arrow-down-circle" :href="route('accounting.vouchers.create', ['type' => 'receipt'])">Receive money</x-admin.button>
            <x-admin.button icon="arrow-up-circle" :href="route('accounting.vouchers.create', ['type' => 'expense'])">Record expense</x-admin.button>
        @endif
    </x-admin.page-header>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex gap-2">
                <select name="type" class="form-select" style="width:160px;min-height:40px" onchange="this.form.submit()"><option value="">All vouchers</option><option value="expense" @selected(request('type')==='expense')>Expenses</option><option value="receipt" @selected(request('type')==='receipt')>Receipts</option></select>
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover align-middle">
            <thead><tr><th>Reference</th><th>Date</th><th>Type</th><th>Memo</th><th class="text-end">Amount</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($vouchers as $v)
                <tr>
                    <td><a href="{{ route('accounting.journals.show', $v) }}" class="fw-semibold text-decoration-none">{{ $v->reference }}</a></td>
                    <td class="small">{{ $v->date->format('d M Y') }}</td>
                    <td><x-admin.badge :tone="$v->source === 'expense' ? 'danger' : 'success'">{{ ucfirst($v->source) }}</x-admin.badge></td>
                    <td class="small">{{ $v->memo ?: '—' }}</td>
                    <td class="text-end fw-semibold">{{ number_format($v->total ?? 0, 2) }}</td>
                    <td class="text-end"><a href="{{ route('accounting.journals.show', $v) }}" class="btn btn-action"><i class="bi bi-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-admin.empty icon="receipt" text="No vouchers yet. Record an expense or a receipt to get started." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($vouchers->hasPages())
        <x-slot:footer>{{ $vouchers->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
