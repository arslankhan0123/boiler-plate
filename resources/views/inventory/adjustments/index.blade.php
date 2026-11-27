@extends('layouts.admin.main')
@section('title', $meta['title'])
@section('subtitle', 'Inventory')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$meta['title']" :subtitle="$kind === 'damage' ? 'Broken, expired or lost stock — removed from the warehouse.' : 'Correct stock after a count: add or remove quantities.'">
        @if ($canEdit)<x-admin.button icon="plus-lg" :href="route($meta['route'].'.create')">New {{ strtolower($meta['one']) }}</x-admin.button>@endif
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
            <thead><tr><th>Date</th><th>Reference</th><th>Warehouse</th><th>Items</th><th class="text-end">Total qty</th><th>Note</th><th>By</th><th class="text-end"></th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td class="text-nowrap">{{ $r->date->format('d M Y') }}</td>
                    <td><a href="{{ route($meta['route'].'.show', $r) }}" class="fw-semibold text-decoration-none">{{ $r->reference_no }}</a></td>
                    <td>{{ $r->warehouse->name ?? '—' }}</td>
                    <td>{{ $r->items_count }}</td>
                    <td class="text-end fw-bold {{ $kind === 'damage' ? 'text-danger' : '' }}">{{ $kind === 'damage' ? '−' : '' }}{{ \App\Support\Inventory\StockService::fmt((float) $r->items_sum_qty) }}</td>
                    <td class="small text-body-secondary text-truncate" style="max-width:220px">{{ $r->note }}</td>
                    <td class="small">{{ $users[$r->created_by] ?? '—' }}</td>
                    <td class="text-end text-nowrap">
                        <a href="{{ route($meta['route'].'.show', $r) }}" class="btn btn-action"><i class="bi bi-eye"></i></a>
                        @if ($canEdit)
                            <a href="{{ route($meta['route'].'.edit', $r) }}" class="btn btn-action"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route($meta['route'].'.destroy', $r)" confirm="Delete this entry? Stock will be restored." />
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><x-admin.empty :icon="$meta['icon']" text="Nothing recorded yet." /></td></tr>
            @endforelse
            </tbody></table></div>
        @if ($rows->hasPages())
            <x-slot:footer>{{ $rows->links('pagination::bootstrap-5') }}</x-slot:footer>
        @endif
    </x-admin.card>
</div>
@endsection
