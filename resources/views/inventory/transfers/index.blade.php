@extends('layouts.admin.main')
@section('title', 'Stock Transfers')
@section('subtitle', 'Inventory')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Stock Transfers" subtitle="Move stock between warehouses and shops.">
        @if ($canEdit)<x-admin.button icon="plus-lg" :href="route('inventory.transfers.create')">New transfer</x-admin.button>@endif
    </x-admin.page-header>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:200px;min-height:40px" placeholder="Reference or note…">
                <select name="warehouse" class="form-select" style="width:170px;min-height:40px" onchange="this.form.submit()"><option value="">All warehouses</option>@foreach ($warehouses as $id => $n)<option value="{{ $id }}" @selected(request('warehouse') == $id)>{{ $n }}</option>@endforeach</select>
                <input type="date" name="from" value="{{ request('from') }}" class="form-control" style="width:150px;min-height:40px" onchange="this.form.submit()">
                <input type="date" name="to" value="{{ request('to') }}" class="form-control" style="width:150px;min-height:40px" onchange="this.form.submit()">
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover">
            <thead><tr><th>Date</th><th>Reference</th><th>From → To</th><th>Items</th><th class="text-end">Total qty</th><th>By</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td class="text-nowrap">{{ $r->date->format('d M Y') }}</td>
                    <td><a href="{{ route('inventory.transfers.show', $r) }}" class="fw-semibold text-decoration-none">{{ $r->reference_no }}</a></td>
                    <td>{{ $r->from->name ?? '—' }} <i class="bi bi-arrow-right text-body-secondary"></i> {{ $r->to->name ?? '—' }}</td>
                    <td>{{ $r->items_count }}</td>
                    <td class="text-end fw-bold">{{ \App\Support\Inventory\StockService::fmt((float) $r->items_sum_qty) }}</td>
                    <td class="small">{{ $users[$r->created_by] ?? '—' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route('inventory.transfers.show', $r) }}" class="btn btn-action"><i class="bi bi-eye"></i></a>
                        @if ($canEdit)
                            <a href="{{ route('inventory.transfers.edit', $r) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route('inventory.transfers.destroy', $r)" confirm="Delete this transfer? Stock goes back to the source warehouse." />
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><x-admin.empty icon="arrow-left-right" text="No transfers yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($rows->hasPages())
            <x-slot:footer>{{ $rows->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
