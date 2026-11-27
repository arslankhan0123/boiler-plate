@extends('layouts.admin.main')
@php
    $sale = $meta['side'] === 'sale';
    $editing = $doc->exists;
    $title = ($editing ? 'Edit ' : 'New ').strtolower($meta['one']);
    $cur = \App\Support\Settings::get('default_currency', 'PKR');
    $isReturn = in_array($meta['type'], ['sale_return', 'purchase_return'], true);
    $isPurchase = $meta['type'] === 'purchase';
    $partyLabel = $sale ? 'Customer' : 'Supplier';
    $oldItems = old('items');
    if (is_array($oldItems)) {
        $map = \App\Models\Inventory\Item::query()->withTrashed()->with('tax', 'variant', 'unit', 'stocks')->whereIn('id', collect($oldItems)->pluck('item_id')->filter())->get()->keyBy('id');
        $lines = collect($oldItems)->filter(fn ($r) => ! empty($r['item_id']) && isset($map[$r['item_id']]))
            ->map(fn ($r) => \App\Http\Controllers\Inventory\ItemController::json($map[$r['item_id']], (int) old('warehouse_id', $doc->warehouse_id)) + $r)->values();
    }
@endphp
@section('title', $title)
@section('subtitle', ($sale ? 'Sales' : 'Purchase').' · '.$meta['many'])

@section('content')
<div class="app-content">
    <x-admin.page-header :title="$title" :subtitle="$editing ? $doc->number : ($doc->parent_id ? 'From '.optional($doc->parent)->number : null)" :back="route($meta['route'].'.index')" />

    <form method="POST" action="{{ $editing ? route($meta['route'].'.update', $doc) : route($meta['route'].'.store') }}" id="docForm" autocomplete="off">
        @csrf @if ($editing) @method('PUT') @endif
        @if ($doc->parent_id)<input type="hidden" name="parent_id" value="{{ $doc->parent_id }}">@endif
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0 small">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

        <div class="app-card mb-4"><div class="app-card__body">
            <div class="row g-3">
                <div class="col-lg-4">
                    <label class="form-label">{{ $partyLabel }} @if (! $sale || $meta['type'] !== 'invoice')<span class="req">*</span>@endif</label>
                    <div id="partyWrap">
                        <input type="hidden" name="{{ $sale ? 'customer_id' : 'supplier_id' }}" data-party-id value="{{ old($sale ? 'customer_id' : 'supplier_id', $sale ? $doc->customer_id : $doc->supplier_id) }}">
                        <input class="form-control" data-party-search placeholder="Search {{ strtolower($partyLabel) }} by name / mobile…">
                        <div class="pp-info" data-party-info></div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4"><x-admin.input name="date" type="date" label="Date" :value="$doc->date?->toDateString()" required /></div>
                @if (in_array($meta['type'], ['invoice', 'quotation', 'delivery', 'order'], true))
                    <div class="col-lg-2 col-md-4"><x-admin.input name="due_date" type="date" :label="['invoice' => 'Due date', 'quotation' => 'Valid until', 'delivery' => 'Delivery date', 'order' => 'Expected on'][$meta['type']]" :value="$doc->due_date?->toDateString()" optional /></div>
                @endif
                <div class="col-lg-2 col-md-4"><x-admin.select name="status" label="Status" :options="$meta['statuses']" :value="$doc->status" required /></div>
                <div class="col-lg-2 col-md-4"><x-admin.select name="warehouse_id" label="Warehouse" :options="$warehouses" :value="$doc->warehouse_id" required /></div>
                <div class="col-lg-2 col-md-4"><x-admin.input name="reference_no" label="Reference no." :value="$doc->reference_no" optional /></div>
                @if ($sale && $salesmen->isNotEmpty())
                    <div class="col-lg-2 col-md-4"><x-admin.select name="salesman_id" label="Salesman" :options="$salesmen" :value="$doc->salesman_id" placeholder="—" optional /></div>
                @endif
                @if ($isPurchase)
                    <div class="col-lg-4 d-flex align-items-end"><label class="d-flex gap-2 align-items-center small mb-3"><input type="checkbox" class="form-check-input mt-0" name="update_prices" value="1" id="updPrices" @checked(old('update_prices', $doc->update_prices))> Update item cost & sales price from this purchase</label></div>
                @endif
            </div>
        </div></div>

        <div class="app-card mb-4">
            <div class="p-4 pb-2 d-flex gap-3 align-items-center flex-wrap">
                <div class="flex-grow-1" style="min-width:280px"><input type="text" class="form-control" id="itemSearch" placeholder="Search item by name / code, or scan barcode…"></div>
                @if ($isReturn)<span class="small text-body-secondary">Max qty = sold / bought qty minus what was already returned.</span>@endif
            </div>
            <div class="table-responsive"><table class="table app-table doc-lines mb-0">
                <thead><tr>
                    <th>#</th><th style="min-width:220px">Item</th><th style="width:100px">Qty</th><th style="width:130px">{{ $sale ? 'Price' : 'Unit cost' }}</th>
                    <th style="width:150px">Discount</th><th style="width:140px">Tax</th><th class="text-end">Tax amt</th><th class="text-end">Total</th>
                    @if ($isPurchase)<th class="upd-col" style="width:110px">Margin %</th><th class="upd-col" style="width:120px">Sales price</th>@endif
                    <th></th>
                </tr></thead>
                <tbody id="lines"></tbody>
            </table></div>
            <div class="p-4 text-center text-body-secondary small" id="noLines">No items yet — search or scan above.</div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7 d-grid gap-4 align-content-start">
                @if ($meta['payments'] && $canPay)
                    <div class="app-card"><div class="app-card__body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h3 class="app-aside-title m-0">{{ $isReturn ? 'Refund' : ($sale ? 'Payment received' : 'Payment made') }} {{ $editing ? '(add more)' : '' }}</h3>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-light" id="payFull">Full amount</button>
                                <button type="button" class="btn btn-sm btn-light" id="addPay"><i class="bi bi-plus-lg"></i> Split</button>
                            </div>
                        </div>
                        <div id="payRows" class="d-grid gap-2"></div>
                        @if ($editing && $doc->paid_amount > 0)<div class="form-text">Already paid: {{ $cur }} {{ number_format($doc->paid_amount, 2) }} — manage existing payments on the document page.</div>@endif
                    </div></div>
                @endif
                <div class="app-card"><div class="app-card__body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Note</label><textarea name="note" rows="3" class="form-control">{{ old('note', $doc->note) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Terms & conditions</label><textarea name="terms" rows="3" class="form-control">{{ old('terms', $doc->terms) }}</textarea></div>
                    </div>
                </div></div>
            </div>
            <div class="col-lg-5">
                <div class="app-card"><div class="app-card__body">
                    <h3 class="app-aside-title mb-3">Summary</h3>
                    <div class="tot">
                        <div class="tot-row"><span class="lbl">Subtotal <small id="tCount"></small></span><b id="tSub">0.00</b></div>
                        <div class="tot-row">
                            <span class="lbl">Other charges</span>
                            <div class="tot-in"><input type="number" step="any" min="0" name="other_charges_input" id="otherIn" class="form-control" value="{{ old('other_charges_input', $doc->other_charges_input ?: '') }}" placeholder="0">
                                <select name="other_charges_tax_id" id="otherTax" class="form-select"><option value="" data-rate="0">No tax</option>@foreach ($taxes as $t)<option value="{{ $t->id }}" data-rate="{{ $t->rate }}" @selected(old('other_charges_tax_id', $doc->other_charges_tax_id) == $t->id)>{{ $t->name }} {{ +$t->rate }}%</option>@endforeach</select></div>
                            <b id="tOther">0.00</b>
                        </div>
                        <div class="tot-row">
                            <span class="lbl">Discount</span>
                            <div class="tot-in"><input type="number" step="any" min="0" name="discount_input" id="discIn" class="form-control" value="{{ old('discount_input', $doc->discount_input ?: '') }}" placeholder="0" @readonly(! $canDiscount)>
                                <select name="discount_type" id="discType" class="form-select" @disabled(! $canDiscount)><option value="percentage" @selected(old('discount_type', $doc->discount_type) === 'percentage')>%</option><option value="fixed" @selected(old('discount_type', $doc->discount_type) === 'fixed')>{{ $cur }}</option></select></div>
                            <b class="text-danger" id="tDisc">0.00</b>
                        </div>
                        @if ($sale && ! $isReturn)
                            <div class="tot-row">
                                <span class="lbl">Coupon</span>
                                <div class="tot-in"><input name="coupon_code" id="couponCode" class="form-control text-uppercase" value="{{ old('coupon_code', $doc->coupon_code) }}" placeholder="Code"><button type="button" class="btn btn-light" id="applyCoupon">Apply</button></div>
                                <b class="text-danger" id="tCoupon">0.00</b>
                            </div>
                            <div class="small" id="couponMsg"></div>
                        @endif
                        <div class="tot-row {{ $roundOff ? '' : 'd-none' }}" id="roundRow"><span class="lbl">Round off</span><b id="tRound">0.00</b></div>
                    </div>
                    <div class="tot-grand"><span>Grand total</span><b id="tGrand">0.00</b></div>
                    @if ($meta['payments'] && $canPay)
                        <div class="tot-pay">
                            <div><span>{{ $isReturn ? 'Refunding now' : 'Paying now' }}</span><b id="tPaying">0.00</b></div>
                            <div><span>Balance</span><b id="tBalance">0.00</b></div>
                        </div>
                    @endif
                    <div class="d-grid gap-2 mt-4">
                        <x-admin.button icon="check2" class="btn-lg-app">Save {{ strtolower($meta['one']) }}</x-admin.button>
                        @if (in_array($meta['type'], ['invoice', 'quotation', 'delivery', 'sale_return', 'purchase', 'order'], true))
                            <button type="submit" name="print" value="1" class="btn btn-light"><i class="bi bi-printer me-1"></i> Save & print</button>
                        @endif
                    </div>
                </div></div>
            </div>
        </div>
    </form>
</div>

<template id="lineTpl">
    <tr>
        <td class="ln"></td>
        <td><div class="fw-semibold nm"></div><div class="small text-body-secondary meta"></div><input class="form-control form-control-sm mt-1 desc d-none" data-f="description" placeholder="Description"></td>
        <td><input type="hidden" data-f="item_id"><input type="number" step="any" min="0" class="form-control form-control-sm" data-f="qty"><div class="small mt-1 stockinfo"></div></td>
        <td><input type="number" step="any" min="0" class="form-control form-control-sm" data-f="unit_price"></td>
        <td><div class="disc-grp"><input type="number" step="any" min="0" class="form-control form-control-sm" data-f="discount_input" placeholder="0"><select class="form-select form-select-sm" data-f="discount_type"><option value="percentage">%</option><option value="fixed">{{ $cur }}</option></select></div></td>
        <td><div class="tax-grp"><select class="form-select form-select-sm" data-f="tax_id"><option value="" data-rate="0">None</option>@foreach ($taxes as $t)<option value="{{ $t->id }}" data-rate="{{ $t->rate }}">{{ $t->name }} {{ +$t->rate }}%</option>@endforeach</select>
            <select class="form-select form-select-sm" data-f="tax_type"><option value="exclusive">Exclusive</option><option value="inclusive">Inclusive</option></select></div></td>
        <td class="text-end taxamt"></td><td class="text-end fw-semibold total"></td>
        @if ($isPurchase)<td class="upd-col"><input type="number" step="any" class="form-control form-control-sm" data-f="profit_margin"></td><td class="upd-col"><input type="number" step="any" min="0" class="form-control form-control-sm" data-f="sales_price"></td>@endif
        <td class="text-nowrap text-end"><button type="button" class="btn btn-sm btn-light dsc" title="Description"><i class="bi bi-chat-left-text"></i></button> <button type="button" class="btn btn-sm btn-light text-danger rm"><i class="bi bi-x-lg"></i></button></td>
    </tr>
</template>
@include('inventory.partials.picker')
@include('retail.partials.party-picker')
@endsection

@push('styles')
<style>
    .doc-lines { min-width: 980px; } .doc-lines td { vertical-align: top; padding-top: .7rem; padding-bottom: .7rem; }
    .doc-lines .form-control, .doc-lines .form-select { min-height: 34px !important; height: 34px; padding: .25rem .5rem; font-size: .85rem; border-radius: 8px; }
    .doc-lines .form-select { padding-right: 1.6rem; background-position: right .45rem center; }
    .doc-lines .disc-grp { display: flex; } .doc-lines .disc-grp .form-control { border-radius: 8px 0 0 8px; } .doc-lines .disc-grp .form-select { width: 64px; flex: 0 0 64px; border-radius: 0 8px 8px 0; border-left: 0; }
    .doc-lines .tax-grp { display: grid; gap: 4px; } .doc-lines .btn-sm { height: 32px; width: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
    .doc-lines .stockinfo { font-size: .72rem; white-space: nowrap; } .doc-lines .nm { line-height: 1.25; }
    #docForm.no-upd .upd-col { display: none; }
    .tot { display: grid; } .tot-row { display: grid; grid-template-columns: 110px 1fr 100px; align-items: center; gap: 10px; padding: .55rem 0; border-bottom: 1px dashed var(--app-border); }
    .tot-row:first-child, .tot-row#roundRow { grid-template-columns: 1fr auto; } .tot-row .lbl { color: var(--app-text-muted); font-size: .88rem; } .tot-row .lbl small { display: block; font-size: .72rem; }
    .tot-row b { text-align: right; font-weight: 700; }
    .tot-in { display: flex; } .tot-in .form-control, .tot-in .form-select, .tot-in .btn { min-height: 36px !important; height: 36px; padding: .3rem .55rem; font-size: .85rem; }
    .tot-in .form-control { border-radius: 8px 0 0 8px; min-width: 0; } .tot-in .form-select, .tot-in .btn { border-radius: 0 8px 8px 0; border-left: 0; width: auto; flex: 0 0 auto; max-width: 120px; padding-right: 1.6rem; background-position: right .4rem center; }
    .tot-in .btn { padding-right: .7rem; border: 1px solid var(--app-border); }
    #couponMsg { padding: .2rem 0 0 120px; }
    .tot-grand { display: flex; justify-content: space-between; align-items: center; margin: 14px 0 10px; padding: 14px 16px; border-radius: 14px; background: linear-gradient(135deg, var(--app-primary, #4f46e5), #6366f1); color: #fff; box-shadow: 0 8px 22px rgba(79,70,229,.25); }
    .tot-grand span { font-weight: 600; opacity: .9; } .tot-grand b { font-size: 1.4rem; font-weight: 800; }
    .tot-pay { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; } .tot-pay div { background: var(--app-hover); border-radius: 12px; padding: 10px 12px; display: grid; }
    .tot-pay span { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--app-text-muted); font-weight: 600; } .tot-pay b { font-size: 1.05rem; }
    .pay-row { display: grid; grid-template-columns: 1.1fr 1fr 1.4fr auto; gap: .5rem; } .pay-row .chq { grid-column: 1 / -1; display: none; gap: .5rem; } .pay-row.has-chq .chq { display: flex; }
    .pay-row .form-control, .pay-row .form-select { min-height: 36px !important; height: 36px; font-size: .85rem; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const SALE = @json($sale), RETURN = @json($isReturn), PURCHASE = @json($isPurchase), CAN_DISC = @json($canDiscount), ROUND = @json($roundOff);
    const CUR = @json($cur), methods = @json(array_values($methods));
    const $ = id => document.getElementById(id), num = v => parseFloat(v) || 0, fx = v => (Math.round(v * 100) / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const tbody = $('lines'), tpl = $('lineTpl'); let n = 0, party = @json($party), coupon = { amount: 0, pct: null, fixed: null };

    const priceFor = (it) => {
        let p = SALE ? +it.sales_price : +it.purchase_price;
        if (SALE && party && party.price_level_type && +party.price_level) p = p + (party.price_level_type === 'increase' ? 1 : -1) * p * party.price_level / 100;
        if (!SALE) p = +it.price || +it.purchase_price; // cost before tax
        return Math.round(p * 10000) / 10000;
    };
    const calcLine = tr => {
        const g = f => tr.querySelector(`[data-f=${f}]`);
        const qty = num(g('qty').value), price = num(g('unit_price').value), rate = num(g('tax_id').selectedOptions[0]?.dataset.rate);
        const gross = qty * price, dIn = num(g('discount_input').value);
        let disc = g('discount_type').value === 'percentage' ? gross * dIn / 100 : dIn; disc = Math.min(Math.max(disc, 0), Math.max(gross, 0));
        const base = gross - disc; let tax, total;
        if (g('tax_type').value === 'inclusive') { total = base; tax = rate ? total - total / (1 + rate / 100) : 0; } else { tax = base * rate / 100; total = base + tax; }
        tr.querySelector('.taxamt').textContent = fx(tax); tr.querySelector('.total').textContent = fx(total);
        const max = tr.dataset.max; const si = tr.querySelector('.stockinfo');
        if (max !== undefined && max !== '') { si.textContent = 'Max ' + (+max); si.className = 'small mt-1 stockinfo ' + (qty > +max ? 'text-danger fw-bold' : 'text-body-secondary'); }
        else if (tr.dataset.service !== '1') { const st = +tr.dataset.stock; si.textContent = 'Stock ' + st; si.className = 'small mt-1 stockinfo ' + (SALE && !RETURN && qty > st ? 'text-danger fw-bold' : 'text-body-secondary'); }
        return { total, tax, disc };
    };
    const syncPurchasePrice = (tr, from) => {
        if (!PURCHASE) return; const g = f => tr.querySelector(`[data-f=${f}]`);
        const cost = num(g('unit_price').value), rate = num(g('tax_id').selectedOptions[0]?.dataset.rate);
        const purchase = g('tax_type').value === 'exclusive' ? cost + cost * rate / 100 : cost;
        if (from === 'margin' || from === 'cost') { if (g('profit_margin').value !== '') g('sales_price').value = (Math.round((purchase + purchase * num(g('profit_margin').value) / 100) * 100) / 100); }
        else if (from === 'sales' && purchase > 0) g('profit_margin').value = Math.round((num(g('sales_price').value) - purchase) / purchase * 10000) / 100;
    };
    const recalc = () => {
        let sub = 0, cnt = 0, qtyT = 0;
        tbody.querySelectorAll('tr').forEach((tr, i) => { tr.querySelector('.ln').textContent = i + 1; const r = calcLine(tr); sub += r.total; cnt++; qtyT += num(tr.querySelector('[data-f=qty]').value); });
        $('noLines').style.display = cnt ? 'none' : '';
        const otherIn = num($('otherIn').value), other = otherIn + otherIn * num($('otherTax').selectedOptions[0]?.dataset.rate) / 100;
        const dIn = num($('discIn').value); let disc = $('discType').value === 'percentage' ? sub * dIn / 100 : dIn; disc = Math.min(Math.max(disc, 0), sub + other);
        let cp = 0; if (coupon.pct !== null) cp = sub * coupon.pct / 100; else if (coupon.fixed !== null) cp = Math.min(coupon.fixed, sub);
        const raw = sub + other - disc - cp, round = ROUND ? Math.round(raw) - raw : 0, grand = Math.max(0, raw + round);
        $('tSub').textContent = fx(sub); $('tCount').textContent = cnt ? `(${cnt} items, ${qtyT} qty)` : ''; $('tOther').textContent = fx(other); $('tDisc').textContent = disc ? '−' + fx(disc) : '0.00';
        if ($('tCoupon')) $('tCoupon').textContent = cp ? '−' + fx(cp) : '0.00';
        $('tRound').textContent = fx(round); $('tGrand').textContent = CUR + ' ' + fx(grand);
        const paying = [...document.querySelectorAll('#payRows [data-amt]')].reduce((s, i) => s + num(i.value), 0);
        if ($('tPaying')) { $('tPaying').textContent = fx(paying); $('tBalance').textContent = fx(grand - paying); $('tBalance').className = grand - paying > 0.004 ? 'text-danger' : 'text-success'; }
        window.__grand = grand;
    };

    const addLine = (it, vals = {}) => {
        const ex = !vals.qty && [...tbody.querySelectorAll('tr')].find(tr => tr.querySelector('[data-f=item_id]').value == it.id);
        if (ex) { const q = ex.querySelector('[data-f=qty]'); q.value = num(q.value) + 1; recalc(); q.focus(); return; }
        const tr = tpl.content.firstElementChild.cloneNode(true), i = n++;
        tr.querySelectorAll('[data-f]').forEach(el => el.name = `items[${i}][${el.dataset.f}]`);
        const g = f => tr.querySelector(`[data-f=${f}]`);
        tr.querySelector('.nm').textContent = it.name; tr.querySelector('.meta').textContent = [it.code, it.unit].filter(Boolean).join(' · ');
        tr.dataset.stock = it.stock ?? 0; tr.dataset.service = it.is_service ? '1' : '0'; if (vals.max_qty !== undefined && vals.max_qty !== null) tr.dataset.max = vals.max_qty;
        g('item_id').value = it.id; g('qty').value = vals.qty ?? 1;
        g('unit_price').value = vals.unit_price ?? priceFor(it);
        g('discount_type').value = vals.discount_type ?? (SALE ? it.discount_type : 'percentage'); g('discount_input').value = vals.discount_input ?? (SALE && +it.discount ? it.discount : '');
        g('tax_id').value = vals.tax_id !== undefined ? (vals.tax_id ?? '') : (it.tax_id ?? ''); g('tax_type').value = vals.tax_type ?? it.tax_type ?? 'exclusive';
        if (vals.description) { g('description').value = vals.description; g('description').classList.remove('d-none'); }
        if (PURCHASE) { g('sales_price').value = vals.sales_price ?? it.sales_price; g('profit_margin').value = vals.profit_margin ?? ''; }
        if (SALE && !CAN_DISC) { g('unit_price').readOnly = true; g('discount_input').readOnly = true; g('discount_type').style.pointerEvents = 'none'; }
        tr.addEventListener('input', e => { if (PURCHASE) syncPurchasePrice(tr, e.target.dataset.f === 'profit_margin' ? 'margin' : e.target.dataset.f === 'sales_price' ? 'sales' : 'cost'); recalc(); });
        tr.addEventListener('change', recalc);
        tr.querySelector('.rm').onclick = () => { tr.remove(); recalc(); };
        tr.querySelector('.dsc').onclick = () => { g('description').classList.toggle('d-none'); g('description').focus(); };
        tbody.appendChild(tr); recalc();
        if (!vals.qty) g('qty').select();
    };

    ItemPicker.attach($('itemSearch'), { onPick: it => addLine(it), warehouse: () => $('f_warehouse_id').value, priceField: SALE ? 'sales_price' : 'purchase_price' });
    PartyPicker.attach($('partyWrap'), {
        url: @json($sale ? route('sales.customers.search') : route('purchase.suppliers.search')),
        createUrl: @json($sale ? (auth()->user()->can('sales.customers') ? route('sales.customers.store') : null) : (auth()->user()->can('purchase.suppliers') ? route('purchase.suppliers.store') : null)),
        label: @json(strtolower($partyLabel)), initial: party,
        onPick: p => { party = p; },
    });

    // Payments
    const payRows = $('payRows');
    const addPay = (amount = '', method = methods[0]) => {
        if (!payRows) return;
        const i = payRows.children.length;
        payRows.insertAdjacentHTML('beforeend', `<div class="pay-row">
            <select name="payments[${i}][method]" class="form-select form-select-sm">${methods.map(m => `<option ${m === method ? 'selected' : ''}>${esc(m)}</option>`).join('')}</select>
            <input type="number" step="any" min="0" name="payments[${i}][amount]" data-amt class="form-control form-control-sm" placeholder="Amount" value="${amount}">
            <input name="payments[${i}][note]" class="form-control form-control-sm" placeholder="Note / reference">
            <button type="button" class="btn btn-sm btn-light" title="Cheque details" data-chq><i class="bi bi-credit-card-2-front"></i></button>
            <div class="chq"><input name="payments[${i}][cheque_no]" class="form-control form-control-sm" placeholder="Cheque no."><input type="date" name="payments[${i}][cheque_date]" class="form-control form-control-sm"></div></div>`);
        const row = payRows.lastElementChild; row.querySelector('[data-amt]').addEventListener('input', recalc); row.querySelector('[data-chq]').onclick = () => row.classList.toggle('has-chq');
    };
    if (payRows) { addPay(); $('addPay').onclick = () => addPay(); $('payFull').onclick = () => { const first = payRows.querySelector('[data-amt]'); payRows.querySelectorAll('[data-amt]').forEach(i => i.value = ''); first.value = Math.round(window.__grand * 100) / 100; recalc(); }; }

    // Coupon
    $('applyCoupon')?.addEventListener('click', async () => {
        const code = $('couponCode').value.trim(), msg = $('couponMsg');
        if (!code) { coupon = { amount: 0, pct: null, fixed: null }; msg.textContent = ''; return recalc(); }
        const sub = [...tbody.querySelectorAll('.total')].reduce((s, el) => s + num(el.textContent.replace(/,/g, '')), 0);
        const r = await fetch(@json(route('sales.coupon-check')) + '?' + new URLSearchParams({ code, customer_id: document.querySelector('[data-party-id]').value, subtotal: sub, document_id: @json($doc->id) ?? '' }), { headers: { Accept: 'application/json' } });
        const j = await r.json();
        if (!j.ok) { coupon = { amount: 0, pct: null, fixed: null }; msg.innerHTML = `<span class="text-danger">${esc(j.message)}</span>`; }
        else { coupon = /%\)$/.test(j.label) ? { pct: sub ? j.amount / sub * 100 : 0, fixed: null } : { pct: null, fixed: j.amount }; msg.innerHTML = `<span class="text-success">${esc(j.label)} applied</span>`; }
        recalc();
    });

    ['otherIn', 'otherTax', 'discIn', 'discType'].forEach(id => $(id)?.addEventListener('input', recalc));
    $('otherTax').addEventListener('change', recalc); $('discType').addEventListener('change', recalc); $('otherIn').addEventListener('input', recalc);
    const upd = $('updPrices'); const syncUpd = () => $('docForm').classList.toggle('no-upd', !upd?.checked);
    upd?.addEventListener('change', syncUpd);

    (@json($lines)).forEach(l => addLine(l, l));
    syncUpd(); recalc();
    if (@json((bool) $doc->coupon_code) || $('couponCode')?.value) $('applyCoupon')?.click();
    document.getElementById('docForm').addEventListener('keydown', e => { if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.type !== 'submit') e.preventDefault(); });
})();
</script>
@endpush
