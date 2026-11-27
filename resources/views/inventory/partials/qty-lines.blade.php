{{--
    Qty-only line editor for adjustments / damage / transfers.
    Vars: $lines (collection of item json + qty [+ description]), $signed (bool), $warehouseField (id of the warehouse select), $showDesc (bool)
--}}
<x-admin.card flush title="Items">
    <div class="p-4 pb-2"><input type="text" class="form-control" id="lineSearch" placeholder="Search item by name / code, or scan barcode…" autocomplete="off"></div>
    @error('items')<div class="alert alert-danger mx-4 small">{{ $message }}</div>@enderror
    <div class="table-responsive"><table class="table app-table mb-0">
        <thead><tr><th>#</th><th>Item</th><th class="text-end">In stock</th><th style="width:150px">{{ $signed ? 'Qty (+ add / − remove)' : 'Qty' }}</th>@if ($showDesc ?? false)<th>Note</th>@endif<th></th></tr></thead>
        <tbody id="qtyRows"><tr class="empty-row"><td colspan="6"><x-admin.empty icon="upc-scan" text="No items yet — search or scan above." /></td></tr></tbody>
        <tfoot><tr><th colspan="3" class="text-end">Total qty</th><th id="qtyTotal">0</th><th colspan="2"></th></tr></tfoot>
    </table></div>
</x-admin.card>
@include('inventory.partials.picker')

@push('scripts')
<script>
(() => {
    const tbody = document.getElementById('qtyRows'), total = document.getElementById('qtyTotal'), signed = @json((bool) $signed), desc = @json((bool) ($showDesc ?? false));
    const wh = () => document.getElementById(@json($warehouseField))?.value;
    let n = 0;
    const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const sync = () => {
        let t = 0; tbody.querySelectorAll('tr[data-id]').forEach((tr, i) => { tr.querySelector('.ln').textContent = i + 1; t += parseFloat(tr.querySelector('.q').value) || 0; });
        total.textContent = t.toLocaleString(undefined, { maximumFractionDigits: 3 });
        if (!tbody.querySelector('tr[data-id]') && !tbody.querySelector('.empty-row')) tbody.insertAdjacentHTML('beforeend', '<tr class="empty-row"><td colspan="6" class="text-center text-body-secondary py-4">No items yet.</td></tr>');
    };
    const add = (it, qty = 1, note = '') => {
        tbody.querySelector('.empty-row')?.remove();
        const ex = tbody.querySelector(`tr[data-id="${it.id}"] .q`); if (ex) { ex.value = (+ex.value || 0) + 1; ex.focus(); return sync(); }
        const i = n++;
        tbody.insertAdjacentHTML('beforeend', `<tr data-id="${it.id}"><td class="ln"></td>
            <td><div class="fw-semibold">${esc(it.name)}</div><div class="small text-body-secondary">${esc(it.code)}${it.unit ? ' · ' + esc(it.unit) : ''}</div></td>
            <td class="text-end ${it.stock <= 0 ? 'text-danger' : ''}">${(+it.stock).toLocaleString(undefined, { maximumFractionDigits: 3 })}</td>
            <td><input type="hidden" name="items[${i}][item_id]" value="${it.id}"><input type="number" step="any" ${signed ? '' : 'min="0"'} name="items[${i}][qty]" value="${qty}" class="form-control form-control-sm q" required></td>
            ${desc ? `<td><input name="items[${i}][description]" value="${esc(note)}" class="form-control form-control-sm" placeholder="Reason"></td>` : ''}
            <td class="text-end"><button type="button" class="btn btn-sm btn-light text-danger rm"><i class="bi bi-x-lg"></i></button></td></tr>`);
        const tr = tbody.lastElementChild; tr.querySelector('.q').addEventListener('input', sync); tr.querySelector('.rm').onclick = () => { tr.remove(); sync(); };
        sync();
    };
    ItemPicker.attach(document.getElementById('lineSearch'), { onPick: add, warehouse: wh, stockOnly: true });
    @php $old = old('items'); @endphp
    @if (is_array($old))
        @php $map = \App\Models\Inventory\Item::query()->with('variant', 'unit', 'stocks', 'tax')->whereIn('id', collect($old)->pluck('item_id')->filter())->get()->keyBy('id'); @endphp
        @foreach ($old as $r) @if (! empty($r['item_id']) && isset($map[$r['item_id']])) add(@json(\App\Http\Controllers\Inventory\ItemController::json($map[$r['item_id']])), @json($r['qty'] ?? 1), @json($r['description'] ?? '')); @endif @endforeach
    @else
        @foreach ($lines as $l) add(@json($l), @json($l['qty']), @json($l['description'] ?? '')); @endforeach
    @endif
})();
</script>
@endpush
