@extends('layouts.admin.main')
@php $q = fn ($v) => $v === null ? '' : \App\Support\Inventory\StockService::fmt((float) $v); @endphp
@section('title', 'Count '.$count->reference_no)
@section('subtitle', 'Inventory · Stock Count')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="'Count '.$count->reference_no" :subtitle="$count->date->format('d M Y').' · '.($count->warehouse->name ?? '')" :back="route('inventory.counts.index')">
        @if ($count->status === 'finalized' && $count->adjustment_id)
            <x-admin.badge tone="success" icon="check2-circle">Finalized</x-admin.badge>
            <x-admin.button variant="light" icon="sliders2" :href="route('inventory.adjustments.show', $count->adjustment_id)">View adjustment</x-admin.button>
        @elseif ($count->status === 'finalized')
            <x-admin.badge tone="success" icon="check2-circle">Finalized — no change</x-admin.badge>
        @endif
    </x-admin.page-header>

    <form method="POST" action="{{ $canEdit ? route('inventory.counts.update', $count) : '#' }}" id="countForm">@csrf @method('PUT')
        <x-admin.card flush>
            @if ($canEdit)
                <x-slot:actions>
                    <input type="text" id="countSearch" class="form-control" style="width:240px;min-height:40px" placeholder="Jump to item (name / code)…" autocomplete="off">
                </x-slot:actions>
            @endif
            <div class="table-responsive"><table class="table app-table align-middle">
                <thead><tr><th>#</th><th>Item</th><th class="text-end">System qty</th><th style="width:150px" class="text-end">Counted</th><th class="text-end">Difference</th><th>Note</th></tr></thead>
                <tbody>
                @foreach ($count->items as $i => $line)
                    <tr data-search="{{ strtolower($line->item?->displayName().' '.$line->item?->code) }}">
                        <td>{{ $i + 1 }}</td>
                        <td class="fw-semibold">{{ $line->item?->displayName() }}<div class="small text-body-secondary fw-normal">{{ $line->item->code ?? '' }} {{ $line->item->unit->name ?? '' }}</div></td>
                        <td class="text-end">{{ $q($line->system_qty) }}</td>
                        <td class="text-end">
                            @if ($canEdit)<input type="number" step="any" name="counts[{{ $line->id }}]" value="{{ $line->counted_qty !== null ? rtrim(rtrim(number_format($line->counted_qty, 3, '.', ''), '0'), '.') : '' }}" class="form-control form-control-sm text-end cq" data-sys="{{ $line->system_qty }}" placeholder="—">
                            @else{{ $line->counted_qty !== null ? $q($line->counted_qty) : '—' }}@endif
                        </td>
                        <td class="text-end fw-bold diff">@if ($line->diff() !== null)<span class="{{ $line->diff() < 0 ? 'text-danger' : ($line->diff() > 0 ? 'text-success' : 'text-body-secondary') }}">{{ $line->diff() > 0 ? '+' : '' }}{{ $q($line->diff()) }}</span>@endif</td>
                        <td>@if ($canEdit)<input name="notes[{{ $line->id }}]" value="{{ $line->note }}" class="form-control form-control-sm" placeholder="Reason">@else<span class="small text-body-secondary">{{ $line->note }}</span>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            @if ($canEdit)
                <x-slot:footer>
                    <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
                        <span class="small text-body-secondary">Items: {{ $count->items->count() }} · <span id="countedN">0</span> counted · <span id="diffN">0</span> with differences</span>
                        <div class="d-flex gap-2">
                            <x-admin.button variant="light" icon="save">Save draft</x-admin.button>
                            <button type="submit" formaction="{{ route('inventory.counts.finalize', $count) }}" class="btn btn-primary" onclick="return confirm('Finalise this count? A stock adjustment will be created for the differences and stock will be corrected.')"><i class="bi bi-check2-circle me-1"></i> Finalize & adjust stock</button>
                        </div>
                    </div>
                </x-slot:footer>
            @endif
        </x-admin.card>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const fmt = v => (Math.round(v * 1000) / 1000).toString();
    const recalc = () => {
        let counted = 0, diffs = 0;
        document.querySelectorAll('.cq').forEach(inp => {
            const td = inp.closest('tr').querySelector('.diff');
            if (inp.value === '') { td.innerHTML = ''; return; }
            counted++;
            const d = parseFloat(inp.value) - parseFloat(inp.dataset.sys);
            if (Math.abs(d) > 0.0001) diffs++;
            td.innerHTML = `<span class="${d < 0 ? 'text-danger' : (d > 0 ? 'text-success' : 'text-body-secondary')}">${d > 0 ? '+' : ''}${fmt(d)}</span>`;
        });
        const cn = document.getElementById('countedN'), dn = document.getElementById('diffN');
        if (cn) cn.textContent = counted; if (dn) dn.textContent = diffs;
    };
    document.querySelectorAll('.cq').forEach(i => i.addEventListener('input', recalc));
    recalc();
    const s = document.getElementById('countSearch');
    s?.addEventListener('input', () => {
        const v = s.value.trim().toLowerCase();
        document.querySelectorAll('tbody tr').forEach(tr => tr.style.display = !v || tr.dataset.search.includes(v) ? '' : 'none');
    });
})();
</script>
@endpush
