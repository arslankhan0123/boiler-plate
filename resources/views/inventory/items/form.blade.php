@extends('layouts.admin.main')
@php
    $editing = $item->exists;
    $title = $editing ? 'Edit '.($item->is_service ? 'service' : 'item') : ($item->is_service ? 'New service' : 'New item');
    $cur = \App\Support\Settings::get('default_currency', 'PKR');
    $variants = old('variants', $editing ? $item->children->map(fn ($c) => [
        'id' => $c->id, 'variant_id' => $c->variant_id, 'sku' => $c->sku, 'barcode' => $c->barcode, 'price' => $c->price,
        'sales_price' => $c->sales_price, 'mrp' => $c->mrp, 'stock' => $c->stock, 'code' => $c->code,
    ])->values()->all() : []);
    $sel = function ($name, $label, $type = null) use ($opts, $item) {
        return ['name' => $name, 'label' => $label, 'type' => $type, 'options' => $opts[$type ?? str_replace('_id', '', $name)] ?? [], 'value' => old($name, $item->{$name})];
    };
@endphp
@section('title', $title)
@section('subtitle', 'Inventory · Items')

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$title" :subtitle="$editing ? $item->code : 'Prices, tax, stock and classification.'" :back="$editing ? route('inventory.items.show', $item) : route('inventory.items.index')" />

    <form method="POST" action="{{ $editing ? route('inventory.items.update', $item) : route('inventory.items.store') }}" enctype="multipart/form-data" class="app-form-grid" id="itemForm">
        @csrf @if ($editing) @method('PUT') @endif
        <input type="hidden" name="is_service" value="{{ old('is_service', $item->is_service) ? 1 : 0 }}">
        <div class="d-grid gap-4">
            {{-- Basic --}}
            <div class="app-card app-form-card">
                <div class="form-section-title">Basic</div>
                <div class="row g-4">
                    <div class="col-md-8"><x-admin.input name="name" :label="$item->is_service ? 'Service name' : 'Item name'" :value="$item->name" required autofocus /></div>
                    <div class="col-md-4"><x-admin.input name="code" label="Item code" :value="$item->code" optional :placeholder="$editing ? '' : 'Auto (IT0001)'" /></div>
                    @unless ($item->is_service)
                        @php $barcodeManual = \App\Support\Settings::get('barcode_mode', 'auto') === 'manual'; @endphp
                        <div class="col-md-4"><x-admin.input name="barcode" label="Barcode" :value="$item->barcode" :optional="! $barcodeManual" :required="$barcodeManual && $item->item_group !== 'variant'" :placeholder="$barcodeManual ? 'Scan or enter barcode' : 'Blank = item code'" /></div>
                    @endunless
                    <div class="col-md-4"><x-admin.input name="sku" label="SKU" :value="$item->sku" optional /></div>
                    <div class="col-md-4"><x-admin.input name="hsn" label="HSN / HS code" :value="$item->hsn" optional /></div>
                    <div class="col-md-4">@include('inventory.items.partials.select', $sel('unit_id', 'Unit'))</div>
                    @unless ($item->is_service)
                        <div class="col-md-8">
                            <label class="form-label">Item type</label>
                            <div class="d-flex gap-2">
                                @foreach (['single' => ['Single item', 'One price & stock'], 'variant' => ['With variants', 'e.g. sizes / colours — own price & stock each']] as $k => [$l, $d])
                                    <label class="group-opt"><input type="radio" name="item_group" value="{{ $k }}" @checked(old('item_group', $item->item_group) === $k)><span><b>{{ $l }}</b><small>{{ $d }}</small></span></label>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="item_group" value="single">
                    @endunless
                </div>
            </div>

            {{-- Classification --}}
            <div class="app-card app-form-card">
                <div class="form-section-title">Classification</div>
                <div class="row g-4">
                    <div class="col-md-4">@include('inventory.items.partials.select', $sel('department_id', 'Department'))</div>
                    <div class="col-md-4">@include('inventory.items.partials.select', $sel('category_id', 'Category') + ['parentField' => 'department_id'])</div>
                    <div class="col-md-4">@include('inventory.items.partials.select', $sel('subcategory_id', 'Sub category') + ['parentField' => 'category_id'])</div>
                    <div class="col-md-4">@include('inventory.items.partials.select', $sel('brand_id', 'Brand'))</div>
                    @unless ($item->is_service)
                        <div class="col-md-4">@include('inventory.items.partials.select', $sel('color_id', 'Color'))</div>
                        <div class="col-md-4">@include('inventory.items.partials.select', $sel('size_id', 'Size'))</div>
                        <div class="col-md-4"><x-admin.select name="supplier_id" label="Supplier" :options="$suppliers" :value="$item->supplier_id" placeholder="—" optional /></div>
                    @endunless
                </div>
            </div>

            @unless ($item->is_service)
                {{-- Storage & stock control --}}
                <div class="app-card app-form-card">
                    <div class="form-section-title">Storage & stock control</div>
                    <div class="row g-4">
                        <div class="col-md-4">@include('inventory.items.partials.select', $sel('rack_id', 'Rack'))</div>
                        <div class="col-md-4">@include('inventory.items.partials.select', $sel('bin_id', 'Bin'))</div>
                        <div class="col-md-4">@include('inventory.items.partials.select', $sel('sub_bin_id', 'Sub bin') + ['parentField' => 'bin_id'])</div>
                        <div class="col-md-3"><x-admin.input name="alert_qty" type="number" step="any" min="0" label="Alert qty" :value="$item->alert_qty ?: ''" optional hint="Low-stock warning at or below this." /></div>
                        <div class="col-md-3"><x-admin.input name="min_qty" type="number" step="any" min="0" label="Minimum qty" :value="$item->min_qty" optional /></div>
                        <div class="col-md-3"><x-admin.input name="max_qty" type="number" step="any" min="0" label="Maximum qty" :value="$item->max_qty" optional /></div>
                        <div class="col-md-3"><x-admin.input name="reorder_qty" type="number" step="any" min="0" label="Reorder qty" :value="$item->reorder_qty" optional /></div>
                        <div class="col-md-6"><x-admin.input name="lot_number" label="Lot / batch no." :value="$item->lot_number" optional /></div>
                        <div class="col-md-6"><x-admin.input name="expire_date" type="date" label="Expiry date" :value="$item->expire_date?->toDateString()" optional /></div>
                    </div>
                </div>
            @endunless

            {{-- Pricing --}}
            <div class="app-card app-form-card">
                <div class="form-section-title">Pricing & tax</div>
                <div class="row g-4">
                    <div class="col-md-4"><x-admin.input name="price" id="pPrice" type="number" step="any" min="0" :label="'Cost price ('.$cur.')'" :value="$item->price ?: ''" hint="Price before tax." /></div>
                    <div class="col-md-4">
                        <label class="form-label" for="pTax">Tax</label>
                        <select name="tax_id" id="pTax" class="form-select">
                            <option value="" data-rate="0">No tax</option>
                            @foreach ($taxes as $t)<option value="{{ $t->id }}" data-rate="{{ $t->rate }}" @selected(old('tax_id', $item->tax_id) == $t->id)>{{ $t->name }} ({{ rtrim(rtrim(number_format($t->rate, 3), '0'), '.') }}%)</option>@endforeach
                        </select>
                        <div class="form-text"><a href="{{ route('settings.taxes') }}" target="_blank">Manage tax rates</a></div>
                    </div>
                    <div class="col-md-4"><x-admin.select name="tax_type" id="pTaxType" label="Tax type" :options="['exclusive' => 'Exclusive (tax added on top)', 'inclusive' => 'Inclusive (tax in price)']" :value="$item->tax_type" required /></div>
                    <div class="col-md-4"><label class="form-label">Purchase price</label><input id="pPurchase" class="form-control" readonly tabindex="-1"><div class="form-text">Cost incl. tax.</div></div>
                    <div class="col-md-4"><x-admin.input name="profit_margin" id="pMargin" type="number" step="any" label="Profit margin %" :value="$item->profit_margin" optional /></div>
                    <div class="col-md-4"><x-admin.input name="sales_price" id="pSales" type="number" step="any" min="0" :label="'Sales price ('.$cur.')'" :value="$item->sales_price ?: ''" required /></div>
                    <div class="col-md-4"><x-admin.input name="mrp" type="number" step="any" min="0" label="MRP" :value="$item->mrp" optional hint="Max retail price (printed on labels)." /></div>
                    <div class="col-md-4"><x-admin.select name="discount_type" label="Default discount" :options="['percentage' => 'Percentage %', 'fixed' => 'Fixed amount']" :value="$item->discount_type" required /></div>
                    <div class="col-md-4"><x-admin.input name="discount" type="number" step="any" min="0" label="Discount value" :value="$item->discount ?: ''" optional /></div>
                    <div class="col-md-4"><x-admin.input name="seller_points" type="number" step="any" min="0" label="Seller points" :value="$item->seller_points ?: ''" optional hint="Points earned by the salesman per unit." /></div>
                </div>
            </div>

            @unless ($item->is_service)
                {{-- Opening stock (single) --}}
                <div class="app-card app-form-card" id="singleStock">
                    <div class="form-section-title">{{ $editing ? 'Opening stock adjustment' : 'Opening stock' }}</div>
                    <div class="row g-4">
                        <div class="col-md-6"><x-admin.select name="opening_warehouse_id" label="Warehouse" :options="$warehouses" :value="$defaultWarehouse" required /></div>
                        <div class="col-md-6"><x-admin.input name="opening_qty" type="number" step="any" :label="$editing ? 'Add / remove qty (+/-)' : 'Opening qty'" optional
                            :hint="$editing ? 'Current stock: '.\App\Support\Inventory\StockService::fmt($item->stock).' — opening so far: '.\App\Support\Inventory\StockService::fmt($item->opening_stock) : 'Stock you already have.'" /></div>
                    </div>
                </div>

                {{-- Variants --}}
                <div class="app-card app-form-card" id="variantBox">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-section-title m-0">Variants</div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('inventory.masters.type', 'variant') }}" target="_blank" class="btn btn-sm btn-light">Manage variants</a>
                            <button type="button" class="btn btn-sm btn-primary" id="addVariant"><i class="bi bi-plus-lg"></i> Add variant</button>
                        </div>
                    </div>
                    <p class="small text-body-secondary mt-2">Each variant gets its own code, barcode, price and stock. Opening qty goes to the warehouse selected above.</p>
                    <div class="table-responsive"><table class="table app-table mb-0">
                        <thead><tr><th style="min-width:160px">Variant</th><th>SKU</th><th>Barcode</th><th>Cost</th><th>Sales price</th><th>MRP</th><th>{{ $editing ? '+/- Qty' : 'Opening qty' }}</th><th></th></tr></thead>
                        <tbody id="variantRows"></tbody>
                    </table></div>
                    @if (empty($opts['variant']))<div class="alert alert-warning small mt-3 mb-0">No variants yet — <a href="{{ route('inventory.masters.type', 'variant') }}" target="_blank">add variants</a> (e.g. Small, Medium, Large) first.</div>@endif
                </div>
            @endunless
        </div>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body text-center">
                <h3 class="app-aside-title text-start">Image</h3>
                <div class="item-img mb-3" id="imgPreview">@if ($item->imageUrl())<img src="{{ $item->imageUrl() }}" alt="">@else<i class="bi bi-image"></i>@endif</div>
                <input type="file" name="image" accept="image/*" class="form-control" id="imgInput">
                @if ($item->image)<label class="d-flex gap-2 align-items-center small mt-2"><input type="checkbox" class="form-check-input mt-0" name="remove_image" value="1"> Remove image</label>@endif
            </div></div>
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Description</h3>
                <textarea name="description" rows="4" class="form-control" placeholder="Shown on invoices if needed">{{ old('description', $item->description) }}</textarea>
                <label class="d-flex gap-2 align-items-center small mt-3"><input type="hidden" name="is_active" value="0"><input type="checkbox" class="form-check-input mt-0" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))> Active (available for sale)</label>
                <div class="d-grid gap-2 mt-4">
                    <x-admin.button icon="check2">Save</x-admin.button>
                    @unless ($editing)<button type="submit" name="save_new" value="1" class="btn btn-light">Save & add another</button>@endunless
                </div>
            </div></div>
            <x-admin.tips :items="['Purchase price = cost + tax (exclusive) or cost (inclusive).', 'Change the margin to get the sales price, or type a sales price to get the margin.', 'Use + next to a list to add a new category, brand, unit… without leaving the page.']" />
        </div>
    </form>
</div>

<template id="variantTpl">
    <tr>
        <td><input type="hidden" data-f="id"><select class="form-select form-select-sm" data-f="variant_id"><option value="">Choose…</option>@foreach ($opts['variant'] as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select><div class="small text-body-secondary mt-1" data-code></div></td>
        <td><input class="form-control form-control-sm" data-f="sku"></td>
        <td><input class="form-control form-control-sm" data-f="barcode" placeholder="Auto"></td>
        <td><input type="number" step="any" min="0" class="form-control form-control-sm" data-f="price" style="width:100px"></td>
        <td><input type="number" step="any" min="0" class="form-control form-control-sm" data-f="sales_price" style="width:100px"></td>
        <td><input type="number" step="any" min="0" class="form-control form-control-sm" data-f="mrp" style="width:90px"></td>
        <td><input type="number" step="any" class="form-control form-control-sm" data-f="opening_qty" style="width:90px"><div class="small text-body-secondary mt-1" data-stock></div></td>
        <td><button type="button" class="btn btn-sm btn-light text-danger" data-remove><i class="bi bi-x-lg"></i></button></td>
    </tr>
</template>
@endsection

@push('styles')
<style>
    .group-opt { flex: 1; display: flex; gap: .6rem; align-items: flex-start; padding: .7rem .9rem; border: 1.5px solid var(--app-border); border-radius: 12px; cursor: pointer; margin: 0; }
    .group-opt:has(input:checked) { border-color: var(--app-primary, #4f46e5); background: #eef2ff; }
    .group-opt span { display: grid; } .group-opt small { color: var(--app-text-muted); font-size: .75rem; }
    .item-img { width: 140px; height: 140px; margin: 0 auto; border-radius: 16px; border: 2px dashed var(--app-border); display: flex; align-items: center; justify-content: center; overflow: hidden; background: var(--app-hover); }
    .item-img img { width: 100%; height: 100%; object-fit: cover; } .item-img i { font-size: 2.2rem; color: var(--app-text-muted); }
    .quick-add { border: 0; background: none; color: var(--app-primary, #4f46e5); font-size: .8rem; font-weight: 600; padding: 0; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const $ = id => document.getElementById(id), num = v => parseFloat(v) || 0;
    const price = $('pPrice'), tax = $('pTax'), type = $('f_tax_type'), purchase = $('pPurchase'), margin = $('pMargin'), sales = $('pSales');
    const rate = () => num(tax.selectedOptions[0]?.dataset.rate);
    const purchaseOf = p => type.value === 'exclusive' ? p + p * rate() / 100 : p;
    const fix = v => (Math.round(v * 100) / 100).toString();
    const showPurchase = () => { purchase.value = fix(purchaseOf(num(price.value))); };
    const fromMargin = () => { showPurchase(); if (margin.value !== '') { const p = purchaseOf(num(price.value)); sales.value = fix(p + p * num(margin.value) / 100); } };
    const fromSales = () => { const p = purchaseOf(num(price.value)); if (p > 0 && sales.value !== '') margin.value = fix((num(sales.value) - p) / p * 100); };
    [price, tax, type].forEach(el => el?.addEventListener('input', fromMargin));
    tax?.addEventListener('change', fromMargin); type?.addEventListener('change', fromMargin);
    margin?.addEventListener('input', fromMargin); sales?.addEventListener('input', fromSales);
    showPurchase();

    // Dependent selects (category ← department, sub category ← category, sub bin ← bin)
    const parents = @json($catParents);
    document.querySelectorAll('select[data-parent-field]').forEach(child => {
        const parent = document.getElementById('f_' + child.dataset.parentField);
        const filter = () => { const p = parent.value; [...child.options].forEach(o => { if (!o.value) return; const own = parents[o.value]; o.hidden = !!p && own && String(own) !== p; }); if (child.selectedOptions[0]?.hidden) child.value = ''; };
        parent?.addEventListener('change', filter); filter();
    });

    // Quick add for masters
    document.querySelectorAll('[data-quick]').forEach(btn => btn.addEventListener('click', async () => {
        const name = prompt('New ' + btn.dataset.label.toLowerCase() + ' name'); if (!name) return;
        const select = document.getElementById('f_' + btn.dataset.field), parentField = select.dataset.parentField;
        const body = new FormData(); body.append('name', name); body.append('_token', '{{ csrf_token() }}');
        if (parentField && document.getElementById('f_' + parentField)?.value) body.append('parent_id', document.getElementById('f_' + parentField).value);
        const res = await fetch(btn.dataset.url, { method: 'POST', body, headers: { Accept: 'application/json' } });
        if (!res.ok) { const e = await res.json().catch(() => ({})); return window.Toast ? Toast.error(Object.values(e.errors || {})[0]?.[0] || 'Could not add.') : alert('Could not add.'); }
        const r = await res.json(); select.add(new Option(r.name, r.id, true, true)); if (body.get('parent_id')) parents[r.id] = body.get('parent_id');
        window.Toast?.success(r.name + ' added.');
    }));

    // Image preview
    $('imgInput')?.addEventListener('change', e => { const f = e.target.files[0]; if (f) $('imgPreview').innerHTML = '<img src="' + URL.createObjectURL(f) + '">'; });

    // Single / variant
    const box = $('variantBox'), single = $('singleStock'), rows = $('variantRows'), tpl = $('variantTpl');
    let idx = 0;
    const addRow = (v = {}) => {
        const tr = tpl.content.firstElementChild.cloneNode(true), i = idx++;
        tr.querySelectorAll('[data-f]').forEach(el => { el.name = `variants[${i}][${el.dataset.f}]`; if (v[el.dataset.f] !== undefined && v[el.dataset.f] !== null) el.value = v[el.dataset.f]; });
        if (!v.price && v.price !== 0) tr.querySelector('[data-f=price]').value = price.value;
        if (v.code) tr.querySelector('[data-code]').textContent = v.code;
        if (v.stock !== undefined) tr.querySelector('[data-stock]').textContent = 'Stock: ' + (+v.stock);
        tr.querySelector('[data-remove]').onclick = () => tr.remove();
        rows.appendChild(tr);
    };
    (@json(array_values($variants))).forEach(addRow);
    $('addVariant')?.addEventListener('click', () => addRow());
    const syncGroup = () => {
        const g = document.querySelector('input[name=item_group]:checked')?.value || 'single';
        if (box) box.style.display = g === 'variant' ? '' : 'none';
        if (single) single.querySelector('[name=opening_qty]').closest('.col-md-6').style.display = g === 'variant' ? 'none' : '';
        if (g === 'variant' && rows && !rows.children.length) addRow();
    };
    document.querySelectorAll('input[name=item_group]').forEach(r => r.addEventListener('change', syncGroup)); syncGroup();
})();
</script>
@endpush
