@extends('layouts.admin.main')
@section('title', 'Barcode Labels')
@section('subtitle', 'Inventory')
@php $cur = \App\Support\Settings::get('default_currency', 'PKR'); @endphp

@section('content')
<div class="app-content">
    <x-admin.page-header title="Barcode Labels" subtitle="Pick items, set how many labels each, then print." :back="route('inventory.items.index')" />

    <form method="POST" action="{{ route('inventory.labels') }}" class="app-form-grid no-print">@csrf
        <x-admin.card flush title="Items">
            <div class="p-4 pb-2"><input type="text" class="form-control" id="labelSearch" placeholder="Search or scan an item to add…" autocomplete="off"></div>
            <div class="table-responsive"><table class="table app-table mb-0">
                <thead><tr><th>Item</th><th>Barcode</th><th class="text-end">Price</th><th style="width:130px">Labels</th><th></th></tr></thead>
                <tbody id="labelRows"><tr class="empty-row"><td colspan="5"><x-admin.empty icon="upc-scan" text="Add items above." /></td></tr></tbody>
            </table></div>
        </x-admin.card>
        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Label options</h3>
                <div class="d-grid gap-2 small">
                    @foreach (['show_name' => ['Item name', true], 'show_price' => ['Price', true], 'show_code' => ['Barcode number', true], 'show_company' => ['Company name', false]] as $k => [$l, $d])
                        <label class="d-flex gap-2 align-items-center"><input type="hidden" name="{{ $k }}" value="0"><input type="checkbox" class="form-check-input mt-0" name="{{ $k }}" value="1" @checked($print ? $opts[str_replace('show_', '', $k)] : $d)> {{ $l }}</label>
                    @endforeach
                </div>
                <x-admin.select name="per_row" label="Labels per row" :options="[1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6']" :value="$opts['per_row']" class="mt-3" />
                <x-admin.button icon="printer" class="w-100 mt-2">Preview & Print</x-admin.button>
            </div></div>
        </div>
    </form>

    @if ($print)
        <div class="app-card mt-4"><div class="app-card__body">
            <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                <h3 class="app-aside-title m-0">Preview — {{ $rows->sum('copies') }} labels</h3>
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
            </div>
            <div class="label-sheet" style="grid-template-columns: repeat({{ $opts['per_row'] }}, 1fr)">
                @foreach ($rows as $r)
                    @for ($i = 0; $i < $r['copies']; $i++)
                        <div class="label">
                            @if ($opts['company'])<div class="l-company">{{ \App\Support\Settings::companyName() }}</div>@endif
                            @if ($opts['name'])<div class="l-name">{{ \Illuminate\Support\Str::limit($r['item']->displayName(), 40) }}</div>@endif
                            <svg class="l-bar" data-code="{{ $r['item']->barcode ?: $r['item']->code }}" data-text="{{ $opts['code'] ? 1 : 0 }}"></svg>
                            @if ($opts['price'])<div class="l-price">{{ $cur }} {{ number_format($r['item']->sales_price, 2) }}@if ($r['item']->mrp && $r['item']->mrp > $r['item']->sales_price) <s>{{ number_format($r['item']->mrp, 2) }}</s>@endif</div>@endif
                        </div>
                    @endfor
                @endforeach
            </div>
        </div></div>
    @endif
</div>
@include('inventory.partials.picker')
@endsection

@push('styles')
<style>
    .label-sheet { display: grid; gap: 8px; }
    .label { border: 1px dashed #cbd5e1; border-radius: 6px; padding: 6px; text-align: center; page-break-inside: avoid; }
    .l-company { font-size: 10px; font-weight: 700; } .l-name { font-size: 11px; font-weight: 600; line-height: 1.2; }
    .l-bar { max-width: 100%; height: 48px; } .l-price { font-size: 12px; font-weight: 700; } .l-price s { font-weight: 400; color: #64748b; }
    @media print {
        .no-print, .app-sidebar, .app-header, .app-footer, .app-page-header, aside, nav, header, footer { display: none !important; }
        .app-card { box-shadow: none !important; border: 0 !important; } .label { border-color: #e2e8f0; }
        body, .app-main, .app-content { padding: 0 !important; margin: 0 !important; background: #fff !important; }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
(() => {
    document.querySelectorAll('.l-bar').forEach(el => { try { JsBarcode(el, el.dataset.code, { height: 40, width: 1.4, fontSize: 11, margin: 2, displayValue: el.dataset.text === '1' }); } catch (e) {} });
    const tbody = document.getElementById('labelRows'); let n = 0;
    const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const add = (it, copies = 1) => {
        tbody.querySelector('.empty-row')?.remove();
        const ex = tbody.querySelector(`tr[data-id="${it.id}"] input[type=number]`); if (ex) { ex.value = +ex.value + 1; return; }
        const i = n++;
        tbody.insertAdjacentHTML('beforeend', `<tr data-id="${it.id}"><td class="fw-semibold">${esc(it.name)}<div class="small text-body-secondary">${esc(it.code)}</div></td>
            <td class="small">${esc(it.barcode || it.code)}</td><td class="text-end">${(+it.sales_price).toFixed(2)}</td>
            <td><input type="hidden" name="rows[${i}][item_id]" value="${it.id}"><input type="number" min="1" max="500" name="rows[${i}][copies]" value="${copies}" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-sm btn-light text-danger" onclick="this.closest('tr').remove()"><i class="bi bi-x-lg"></i></button></td></tr>`);
    };
    ItemPicker.attach(document.getElementById('labelSearch'), { onPick: add, stockOnly: true });
    @foreach ($rows as $r) add(@json(\App\Http\Controllers\Inventory\ItemController::json($r['item'])), {{ $r['copies'] }}); @endforeach
    @foreach ($preselect as $p) add(@json(\App\Http\Controllers\Inventory\ItemController::json($p)), 1); @endforeach
})();
</script>
@endpush
