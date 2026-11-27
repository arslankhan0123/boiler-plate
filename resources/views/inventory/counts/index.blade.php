@extends('layouts.admin.main')
@php $q = fn ($v) => \App\Support\Inventory\StockService::fmt((float) $v); @endphp
@section('title', 'Stock Count')
@section('subtitle', 'Inventory')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Stock Count" subtitle="Count physical stock on a sheet; finalising adjusts the system to match.">
        @if ($canEdit)<x-admin.button icon="plus-lg" :href="route('inventory.counts.create')">New count</x-admin.button>@endif
    </x-admin.page-header>
    <x-admin.card flush>
        <x-slot:actions>
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input name="q" value="{{ request('q') }}" class="form-control" style="width:180px;min-height:40px" placeholder="Reference…">
                <select name="status" class="form-select" style="width:140px;min-height:40px" onchange="this.form.submit()"><option value="">Any status</option><option value="draft" @selected(request('status') === 'draft')>Draft</option><option value="finalized" @selected(request('status') === 'finalized')>Finalized</option></select>
                <select name="warehouse" class="form-select" style="width:160px;min-height:40px" onchange="this.form.submit()"><option value="">All warehouses</option>@foreach ($warehouses as $id => $n)<option value="{{ $id }}" @selected(request('warehouse') == $id)>{{ $n }}</option>@endforeach</select>
            </form>
        </x-slot:actions>
        <div class="table-responsive"><table class="table app-table table-hover">
            <thead><tr><th>Reference</th><th>Date</th><th>Warehouse</th><th>Items</th><th>Status</th><th>By</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td><a href="{{ route('inventory.counts.show', $r) }}" class="fw-semibold text-decoration-none">{{ $r->reference_no }}</a></td>
                    <td class="small">{{ $r->date->format('d M Y') }}</td>
                    <td>{{ $r->warehouse->name ?? '—' }}</td>
                    <td>{{ $r->items_count }}</td>
                    <td><x-admin.badge :tone="$r->status === 'finalized' ? 'success' : 'warning'">{{ ucfirst($r->status) }}</x-admin.badge></td>
                    <td class="small">{{ $users[$r->created_by] ?? '—' }}</td>
                    <td class="text-end"><a href="{{ route('inventory.counts.show', $r) }}" class="btn btn-action"><i class="bi bi-eye"></i></a>
                        @if ($canEdit && $r->status === 'draft')<x-admin.delete-button :action="route('inventory.counts.destroy', $r)" confirm="Delete this count sheet?" />@endif</td>
                </tr>
            @empty
                <tr><td colspan="7"><x-admin.empty icon="clipboard-check" text="No stock counts yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($rows->hasPages())
            <x-slot:footer>{{ $rows->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
