@extends('layouts.admin.main')
@php $cur = \App\Support\Settings::get('default_currency', 'PKR'); @endphp
@section('title', 'POS')
@section('subtitle', $editing ? 'Editing '.$editing['number'] : 'Point of sale')

@section('content')
<div class="pos">
    {{-- LEFT: products --}}
    <div class="pos-left">
        <div class="pos-bar">
            <input type="text" id="posSearch" class="form-control" placeholder="Scan barcode or search item…  (F2)" autocomplete="off">
            <select id="posCat" class="form-select"><option value="">All categories</option>@foreach ($categories as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select>
            <select id="posBrand" class="form-select"><option value="">All brands</option>@foreach ($brands as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select>
        </div>
        <div class="pos-grid" id="posGrid"><div class="text-body-secondary p-4">Loading items…</div></div>
        <div class="pos-foot small text-body-secondary">
            <span>
                Today (you): <b>{{ $today->n ?? 0 }}</b> sales · <b>{{ $cur }} {{ number_format($today->total ?? 0, 2) }}</b>
                @if ($register)
                    @if ($register['open'])
                        · <a href="{{ $register['show_url'] }}" class="text-decoration-none"><i class="bi bi-cash-coin text-success"></i> Register open — drawer {{ $cur }} {{ number_format($register['expected'], 2) }}</a>
                    @elseif ($canRegister)
                        · <a href="{{ route('pos.registers.open') }}" class="text-decoration-none"><i class="bi bi-unlock"></i> Open register</a>
                    @endif
                @endif
            </span>
            <span>F2 search · F4 pay · F8 hold · F9 held list · Esc close</span>
        </div>
    </div>

    {{-- RIGHT: cart --}}
    <div class="pos-right">
        <div class="pos-head">
            @if ($editing)<div class="alert alert-warning py-2 small mb-2"><i class="bi bi-pencil-square"></i> Editing invoice <b>{{ $editing['number'] }}</b> — saving replaces it. <a href="{{ route('pos') }}">Cancel</a></div>@endif
            <div id="custWrap" class="mb-2">
                <input type="hidden" data-party-id id="custId">
                <div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span><input class="form-control" data-party-search placeholder="Customer (walk-in)"></div>
                <div class="pp-info" data-party-info></div>
            </div>
            <div class="d-flex gap-2">
                <select id="posWh" class="form-select form-select-sm">@foreach ($warehouses as $id => $n)<option value="{{ $id }}" @selected($id == ($editing['warehouse_id'] ?? $defaultWarehouse))>{{ $n }}</option>@endforeach</select>
                @if ($salesmen->isNotEmpty())<select id="posSm" class="form-select form-select-sm"><option value="">Salesman —</option>@foreach ($salesmen as $id => $n)<option value="{{ $id }}" @selected($id == ($editing['salesman_id'] ?? null))>{{ $n }}</option>@endforeach</select>@endif
            </div>
        </div>
        <div class="pos-cart">
            <table class="table table-sm mb-0">
                <thead><tr><th>Item</th><th style="width:118px" class="text-center">Qty</th><th style="width:92px" class="text-end">Price</th><th class="text-end" style="width:90px">Total</th><th style="width:28px"></th></tr></thead>
                <tbody id="cart"></tbody>
            </table>
            <div id="cartEmpty" class="text-center text-body-secondary py-5"><i class="bi bi-cart3 fs-1 d-block mb-2"></i>Scan or tap an item to start.</div>
        </div>
        <div class="pos-totals">
            <div class="row g-2 mb-2">
                <div class="col-4"><label class="small text-body-secondary">Other charges</label><input type="number" step="any" min="0" id="otherIn" class="form-control form-control-sm" placeholder="0"></div>
                <div class="col-4"><label class="small text-body-secondary">Discount</label><div class="input-group input-group-sm"><input type="number" step="any" min="0" id="discIn" class="form-control" placeholder="0" @readonly(! $canDiscount)><select id="discType" class="form-select" style="max-width:62px" @disabled(! $canDiscount)><option value="percentage">%</option><option value="fixed">{{ $cur }}</option></select></div></div>
                <div class="col-4"><label class="small text-body-secondary">Coupon</label><div class="input-group input-group-sm"><input id="couponCode" class="form-control text-uppercase" placeholder="Code"><button class="btn btn-light" id="applyCoupon" type="button"><i class="bi bi-check2"></i></button></div></div>
            </div>
            <div class="small" id="couponMsg"></div>
            <div class="tot-line"><span>Items <b id="tItems">0</b> · Qty <b id="tQty">0</b></span><span>Subtotal <b id="tSub">0.00</b></span></div>
            <div class="tot-line small text-body-secondary"><span>Tax incl. <span id="tTax">0.00</span></span><span>Charges <span id="tOther">0.00</span> · Disc <span id="tDisc">0.00</span> · Coupon <span id="tCoupon">0.00</span>{!! $roundOff ? ' · Round <span id="tRound">0.00</span>' : '<span id="tRound" class="d-none"></span>' !!}</span></div>
            <div class="grand"><span>Total</span><span>{{ $cur }} <b id="tGrand">0.00</b></span></div>
            <div class="pos-actions">
                @if ($canHold)<button class="btn btn-light" id="btnHold" type="button"><i class="bi bi-pause-circle"></i> Hold</button>
                <button class="btn btn-light position-relative" id="btnHeld" type="button"><i class="bi bi-list-ul"></i> Held <span class="badge bg-danger" id="heldCount">{{ $held }}</span></button>@endif
                <button class="btn btn-light text-danger" id="btnClear" type="button"><i class="bi bi-x-circle"></i> Clear</button>
                <button class="btn btn-success" id="btnCash" type="button"><i class="bi bi-cash"></i> Cash</button>
                <button class="btn btn-primary" id="btnPay" type="button"><i class="bi bi-credit-card"></i> Pay (F4)</button>
            </div>
        </div>
    </div>
</div>

{{-- Pay modal --}}
<div class="modal fade" id="payModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="row g-3">
            <div class="col-md-7">
                <div id="payRows" class="d-grid gap-2"></div>
                <button type="button" class="btn btn-sm btn-light mt-2" id="addPayRow"><i class="bi bi-plus-lg"></i> Split payment</button>
                <div class="d-flex flex-wrap gap-2 mt-3" id="quickCash"></div>
                <textarea id="posNote" rows="2" class="form-control mt-3" placeholder="Note (optional)"></textarea>
            </div>
            <div class="col-md-5">
                <div class="pay-sum">
                    <div><span>Total payable</span><b id="mTotal">0.00</b></div>
                    <div><span>Tendered</span><b id="mPaid">0.00</b></div>
                    <div class="text-success"><span>Change return</span><b id="mChange">0.00</b></div>
                    <div class="text-danger"><span>Balance (credit)</span><b id="mDue">0.00</b></div>
                </div>
                <div class="small text-body-secondary mt-2" id="mAdvance"></div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <label class="me-auto d-flex align-items-center gap-2 small"><input type="checkbox" class="form-check-input mt-0" id="autoPrint" checked> Print receipt</label>
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-lg-app" id="confirmPay"><i class="bi bi-check2-circle"></i> Complete sale</button>
    </div>
</div></div></div>

{{-- Held list modal --}}
<div class="modal fade" id="heldModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Held invoices</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body p-0"><table class="table app-table mb-0"><thead><tr><th>#</th><th>Ref</th><th>Customer</th><th>Items</th><th class="text-end">Total</th><th>Held at</th><th></th></tr></thead><tbody id="heldRows"></tbody></table></div>
</div></div></div>

{{-- Done modal --}}
<div class="modal fade" id="doneModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content text-center p-4">
    <i class="bi bi-check-circle-fill text-success" style="font-size:3rem"></i>
    <h4 class="mt-2 mb-1">Sale complete</h4><div class="text-body-secondary" id="doneNo"></div>
    <div class="fs-3 fw-bold mt-2" id="doneChange"></div>
    <div class="d-flex gap-2 justify-content-center mt-3"><a class="btn btn-light" id="doneReceipt" target="_blank"><i class="bi bi-receipt"></i> Receipt</a><a class="btn btn-light" id="doneA4" target="_blank"><i class="bi bi-printer"></i> A4</a><button class="btn btn-primary" data-bs-dismiss="modal">New sale</button></div>
</div></div></div>

@include('inventory.partials.picker')
@include('retail.partials.party-picker')
@endsection

@push('styles')
<style>
    .app-content:has(.pos) { padding: 0 !important; }
    .pos { display: grid; grid-template-columns: minmax(0, 1fr) 430px; gap: 14px; height: calc(100vh - 150px); min-height: 560px; padding: 14px; }
    .pos-left, .pos-right { background: #fff; border-radius: 16px; border: 1px solid var(--app-border); display: flex; flex-direction: column; min-height: 0; }
    .pos-bar { display: grid; grid-template-columns: 1fr 170px 150px; gap: 8px; padding: 12px; border-bottom: 1px solid var(--app-border); }
    .pos-bar .form-control, .pos-bar .form-select { min-height: 44px; }
    .pos-grid { flex: 1; overflow-y: auto; padding: 12px; display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; align-content: start; }
    .tile { border: 1.5px solid var(--app-border); border-radius: 12px; padding: 8px; cursor: pointer; background: #fff; text-align: left; transition: .12s; position: relative; }
    .tile:hover { border-color: var(--app-primary, #4f46e5); box-shadow: 0 6px 16px rgba(79,70,229,.12); }
    .tile .im { height: 80px; border-radius: 8px; background: var(--app-hover); display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 6px; color: var(--app-text-muted); font-size: 1.6rem; }
    .tile .im img { width: 100%; height: 100%; object-fit: cover; } .tile .nm { font-size: .8rem; font-weight: 600; line-height: 1.2; height: 2.4em; overflow: hidden; }
    .tile .pr { font-weight: 800; color: var(--app-primary, #4f46e5); } .tile .st { position: absolute; top: 6px; right: 6px; font-size: .68rem; background: rgba(255,255,255,.92); border-radius: 6px; padding: 0 5px; }
    .tile.out { opacity: .55; } .tile.out .st { color: #b91c1c; }
    .pos-foot { display: flex; justify-content: space-between; padding: 8px 12px; border-top: 1px solid var(--app-border); }
    .pos-head { padding: 12px; border-bottom: 1px solid var(--app-border); }
    .pos-cart { flex: 1; overflow-y: auto; } .pos-cart td { vertical-align: middle; font-size: .85rem; } .pos-cart thead th { position: sticky; top: 0; background: #f8fafc; font-size: .72rem; text-transform: uppercase; color: var(--app-text-muted); }
    .qty { display: flex; } .qty button { border: 1px solid var(--app-border); background: #fff; width: 28px; } .qty input { width: 52px; text-align: center; border: 1px solid var(--app-border); border-left: 0; border-right: 0; }
    .pos-cart .pz { width: 86px; text-align: right; border: 1px solid transparent; border-radius: 6px; padding: 2px 4px; } .pos-cart .pz:hover, .pos-cart .pz:focus { border-color: var(--app-border); }
    .pos-totals { padding: 12px; border-top: 1px solid var(--app-border); background: #f8fafc; border-radius: 0 0 16px 16px; }
    .tot-line { display: flex; justify-content: space-between; margin-top: 4px; }
    .grand { display: flex; justify-content: space-between; align-items: center; background: var(--app-primary, #4f46e5); color: #fff; border-radius: 12px; padding: 10px 14px; margin: 8px 0; font-size: 1.1rem; } .grand b { font-size: 1.5rem; }
    .pos-left, .pos-right { min-width: 0; overflow: hidden; }
    .pos-right .form-control, .pos-right .form-select, .pos-right .input-group-text { min-height: 36px !important; height: 36px; padding: .3rem .6rem; font-size: .85rem; border-radius: 8px; }
    .pos-right .input-group > * { border-radius: 0; } .pos-right .input-group > :first-child { border-radius: 8px 0 0 8px; } .pos-right .input-group > :last-child { border-radius: 0 8px 8px 0; }
    .pos-right .input-group .form-select { max-width: 58px; padding-right: 1.4rem; background-position: right .3rem center; }
    .pos-right label.small { font-size: .72rem; margin-bottom: 2px; }
    .pos-head { padding: 10px 12px; } .pos-totals { padding: 10px 12px; }
    .tot-line { font-size: .85rem; gap: 8px; flex-wrap: wrap; } .tot-line.small { font-size: .72rem; }
    .grand { padding: 8px 12px; font-size: 1rem; margin: 6px 0 8px; } .grand b { font-size: 1.3rem; }
    .pos-actions { display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; }
    .pos-actions .btn { min-height: 38px; padding: .35rem .4rem; font-weight: 600; font-size: .8rem; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; gap: 4px; }
    .pos-actions #btnHold, .pos-actions #btnHeld, .pos-actions #btnClear { grid-column: span 2; }
    .pos-actions #btnCash, .pos-actions #btnPay { grid-column: span 3; min-height: 46px; font-size: .95rem; }
    .pos-actions .badge { font-size: .65rem; }
    .pos-cart td, .pos-cart th { padding: .35rem .4rem; } .pos-cart td { font-size: .78rem; }
    .pos-cart .ci-name { font-weight: 600; font-size: .8rem; line-height: 1.25; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pos-cart .qty button { height: 26px; font-size: .8rem; line-height: 1; padding: 0; } .pos-cart .qty input { font-size: .78rem; height: 26px !important; }
    .pos-cart .pz { font-size: .78rem; height: 26px; } .pos-cart td.fw-semibold { font-size: .8rem; }
    .pos-cart .qty input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .pos-cart .qty button { width: 24px; } .pos-cart .qty input { width: 44px; height: 28px; } .pos-cart .pz { width: 72px; }
    .pay-row { display: grid; grid-template-columns: 1fr 1fr auto; gap: 6px; } .pay-row .chq { grid-column: 1 / -1; display: none; } .pay-row.has-chq .chq { display: block; }
    .pay-sum div { display: flex; justify-content: space-between; padding: 10px 12px; border-radius: 10px; background: var(--app-hover); margin-bottom: 6px; } .pay-sum b { font-size: 1.2rem; }
    @media (max-width: 1100px) { .pos { grid-template-columns: 1fr; height: auto; } .pos-grid { max-height: 50vh; } }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const NEED_REG = @json($requireRegister && !($register['open'] ?? false)), REG_URL = @json($canRegister ? route('pos.registers.open') : null);
    const CUR = @json($cur), CAN_DISC = @json($canDiscount), ROUND = @json($roundOff), methods = @json(array_values($methods));
    const URL = { search: @json(route('inventory.items.search')), store: @json(route('pos.store')), hold: @json(route('pos.hold')), holds: @json(route('pos.holds')),
        hold1: @json(route('pos.holds.show', 0)).replace(/0$/, ''), void: @json(route('pos.void')), coupon: @json(route('sales.coupon-check')) };
    const CSRF = @json(csrf_token());
    const $ = id => document.getElementById(id), num = v => parseFloat(v) || 0;
    const fx = v => (Math.round((+v || 0) * 100) / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const toast = (m, ok = true, title = null) => window.Toast ? (ok ? Toast.success(m, title || 'Done') : Toast.error(m, title || (/stock/i.test(m) ? 'Out of stock' : 'Cannot complete'))) : alert(m);
    const post = async (url, body, method = 'POST') => {
        const r = await fetch(url, { method, headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF }, body: body ? JSON.stringify(body) : null });
        const j = await r.json().catch(() => ({}));
        if (!r.ok) throw new Error(j.message && !j.errors ? j.message : Object.values(j.errors || {})[0]?.[0] || 'Something went wrong.');
        return j;
    };

    let cart = [], customer = @json($walkIn), walkIn = @json($walkIn), coupon = null, holdId = null, editId = null, holdRef = null;
    const lineCalc = l => {
        const gross = l.qty * l.unit_price, disc = Math.min(Math.max(l.discount_type === 'percentage' ? gross * num(l.discount_input) / 100 : num(l.discount_input), 0), gross);
        const base = gross - disc; let tax, total;
        if (l.tax_type === 'inclusive') { total = base; tax = l.tax_rate ? total - total / (1 + l.tax_rate / 100) : 0; } else { tax = base * l.tax_rate / 100; total = base + tax; }
        return { total, tax };
    };
    const priceFor = it => { let p = +it.sales_price; if (customer?.price_level_type && +customer.price_level) p += (customer.price_level_type === 'increase' ? 1 : -1) * p * customer.price_level / 100; return Math.round(p * 100) / 100; };
    const totals = () => {
        let sub = 0, tax = 0, qty = 0; cart.forEach(l => { const c = lineCalc(l); sub += c.total; tax += c.tax; qty += +l.qty; });
        const other = num($('otherIn').value), dIn = num($('discIn').value);
        let disc = $('discType').value === 'percentage' ? sub * dIn / 100 : dIn; disc = Math.min(Math.max(disc, 0), sub + other);
        const cp = coupon ? (coupon.pct !== null ? sub * coupon.pct / 100 : Math.min(coupon.fixed, sub)) : 0;
        const raw = sub + other - disc - cp, round = ROUND ? Math.round(raw) - raw : 0;
        return { sub, tax, qty, other, disc, cp, round, grand: Math.max(0, raw + round) };
    };
    const render = () => {
        const tb = $('cart'); tb.innerHTML = '';
        cart.forEach((l, i) => {
            const c = lineCalc(l), warn = !l.is_service && l.qty > l.stock;
            tb.insertAdjacentHTML('beforeend', `<tr>
                <td><div class="ci-name ${warn ? 'text-danger' : ''}" title="${esc(l.name)} · ${esc(l.code)}${l.is_service ? '' : ' · stock ' + (+l.stock)}">${esc(l.name)}</div></td>
                <td><div class="qty"><button type="button" data-a="dec" data-i="${i}">−</button><input type="number" step="any" min="0" value="${l.qty}" data-a="qty" data-i="${i}"><button type="button" data-a="inc" data-i="${i}">+</button></div></td>
                <td class="text-end"><input type="number" step="any" min="0" class="pz" value="${l.unit_price}" data-a="price" data-i="${i}" ${CAN_DISC ? '' : 'readonly'}></td>
                <td class="text-end fw-semibold">${fx(c.total)}</td>
                <td><button type="button" class="btn btn-sm btn-link text-danger p-0" data-a="rm" data-i="${i}"><i class="bi bi-x-lg"></i></button></td></tr>`);
        });
        $('cartEmpty').style.display = cart.length ? 'none' : '';
        const t = totals();
        $('tItems').textContent = cart.length; $('tQty').textContent = +t.qty.toFixed(3); $('tSub').textContent = fx(t.sub); $('tTax').textContent = fx(t.tax);
        $('tOther').textContent = fx(t.other); $('tDisc').textContent = fx(t.disc); $('tCoupon').textContent = fx(t.cp); $('tRound').textContent = fx(t.round); $('tGrand').textContent = fx(t.grand);
    };
    const noStock = (l, qty) => !l.is_service && qty > +l.stock + 0.0001;
    const stockMsg = l => +l.stock > 0 ? `Only ${+l.stock} in stock for ${l.name}.` : `${l.name} is out of stock.`;
    const add = (it, vals = {}) => {
        const ex = !vals.qty && cart.find(l => l.id === it.id);
        if (ex) { if (noStock(ex, +ex.qty + 1)) return toast(stockMsg(ex), false); ex.qty = +ex.qty + 1; render(); return; }
        if (!vals.qty && noStock(it, 1)) return toast(stockMsg(it), false);
        cart.push({ ...it, qty: vals.qty ?? 1, unit_price: vals.unit_price ?? priceFor(it), discount_type: vals.discount_type ?? it.discount_type ?? 'percentage',
            discount_input: vals.discount_input ?? (+it.discount || 0), tax_id: vals.tax_id !== undefined ? vals.tax_id : it.tax_id, tax_rate: +it.tax_rate || 0, tax_type: vals.tax_type ?? it.tax_type ?? 'exclusive' });
        render();
        $('cart').parentElement.parentElement.scrollTop = 1e6;
    };
    const logVoid = (l, reason = 'Removed from cart') => post(URL.void, { item_id: l.id, item_name: l.name, qty: l.qty, price: l.unit_price, customer_id: customer?.id, reason }).catch(() => {});
    $('cart').addEventListener('click', e => {
        const b = e.target.closest('[data-a]'); if (!b || b.tagName === 'INPUT') return; const l = cart[+b.dataset.i];
        if (b.dataset.a === 'inc') { if (noStock(l, +l.qty + 1)) return toast(stockMsg(l), false); l.qty = +l.qty + 1; }
        if (b.dataset.a === 'dec') { l.qty = Math.max(0, +l.qty - 1); if (!l.qty) { logVoid({ ...l, qty: 1 }); cart.splice(+b.dataset.i, 1); } else logVoid({ ...l, qty: 1 }, 'Qty reduced'); }
        if (b.dataset.a === 'rm') { logVoid(l); cart.splice(+b.dataset.i, 1); }
        render();
    });
    $('cart').addEventListener('change', e => {
        const el = e.target, l = cart[+el.dataset.i]; if (!l) return;
        if (el.dataset.a === 'qty') { let v = num(el.value); if (noStock(l, v)) { toast(stockMsg(l), false); v = Math.max(0, +l.stock); } if (v < l.qty) logVoid({ ...l, qty: l.qty - v }, 'Qty reduced'); l.qty = v; if (!v) cart.splice(+el.dataset.i, 1); }
        if (el.dataset.a === 'price') l.unit_price = num(el.value);
        render();
    });

    // Products grid
    let gridTimer;
    const loadGrid = async () => {
        const p = new URLSearchParams({ q: $('posSearch').value.trim(), category: $('posCat').value, brand: $('posBrand').value, warehouse: $('posWh').value, limit: 60 });
        const r = await fetch(URL.search + '?' + p, { headers: { Accept: 'application/json' } }); const items = r.ok ? await r.json() : [];
        $('posGrid').innerHTML = items.length ? items.map((it, i) => `<button type="button" class="tile ${!it.is_service && it.stock <= 0 ? 'out' : ''}" data-i="${i}">
            <span class="st">${it.is_service ? 'Service' : (+it.stock).toLocaleString()}</span>
            <div class="im">${it.image ? `<img src="${esc(it.image)}">` : '<i class="bi bi-box"></i>'}</div>
            <div class="nm">${esc(it.name)}</div><div class="pr">${fx(priceFor(it))}</div></button>`).join('') : '<div class="text-body-secondary p-4">No items.</div>';
        $('posGrid').onclick = e => { const t = e.target.closest('.tile'); if (t) add(items[+t.dataset.i]); };
    };
    ['posCat', 'posBrand', 'posWh'].forEach(id => $(id).addEventListener('change', loadGrid));
    $('posSearch').addEventListener('input', () => { clearTimeout(gridTimer); gridTimer = setTimeout(loadGrid, 250); });
    $('posSearch').addEventListener('keydown', async e => {
        if (e.key !== 'Enter') return; e.preventDefault();
        const q = $('posSearch').value.trim(); if (!q) return;
        const r = await fetch(URL.search + '?' + new URLSearchParams({ q, warehouse: $('posWh').value, limit: 5 }), { headers: { Accept: 'application/json' } });
        const items = r.ok ? await r.json() : []; const hit = items.find(i => i.barcode === q || i.code === q) || (items.length === 1 ? items[0] : null);
        if (hit) { add(hit); $('posSearch').value = ''; loadGrid(); } else toast(items.length ? 'Several items match — tap one.' : 'No item matches "' + q + '".', false, items.length ? 'Pick an item' : 'Item not found');
    });

    // Customer
    const picker = PartyPicker.attach($('custWrap'), { url: @json(route('sales.customers.search')), createUrl: @json($canCustomer ? route('sales.customers.store') : null), label: 'customer', initial: customer,
        onPick: p => { customer = p || walkIn; cart.forEach(l => { if (CAN_DISC) return; l.unit_price = priceFor(l); }); render(); } });

    // Totals inputs + coupon
    ['otherIn', 'discIn', 'discType'].forEach(id => $(id).addEventListener('input', render));
    $('applyCoupon').addEventListener('click', async () => {
        const code = $('couponCode').value.trim(), msg = $('couponMsg');
        if (!code) { coupon = null; msg.textContent = ''; return render(); }
        try {
            const sub = totals().sub, j = await fetch(URL.coupon + '?' + new URLSearchParams({ code, customer_id: customer?.id || '', subtotal: sub, document_id: editId || '' }), { headers: { Accept: 'application/json' } }).then(r => r.json());
            if (!j.ok) throw new Error(j.message);
            coupon = /%\)$/.test(j.label) ? { pct: sub ? j.amount / sub * 100 : 0, fixed: null } : { pct: null, fixed: j.amount };
            msg.innerHTML = `<span class="text-success">${esc(j.label)} applied</span>`;
        } catch (e) { coupon = null; msg.innerHTML = `<span class="text-danger">${esc(e.message)}</span>`; }
        render();
    });

    // Payload
    const payload = (payments = []) => ({
        customer_id: customer?.id || null, warehouse_id: $('posWh').value, salesman_id: $('posSm')?.value || null,
        other_charges_input: num($('otherIn').value), discount_input: num($('discIn').value), discount_type: $('discType').value,
        coupon_code: coupon ? $('couponCode').value.trim() : null, note: $('posNote').value || null,
        items: cart.map(l => ({ item_id: l.id, qty: l.qty, unit_price: l.unit_price, discount_type: l.discount_type, discount_input: l.discount_input, tax_id: l.tax_id, tax_type: l.tax_type })),
        payments, hold_id: holdId, edit_id: editId, hold_ref: holdRef,
    });
    const reset = () => { cart = []; coupon = null; holdId = null; editId = null; holdRef = null; ['otherIn', 'discIn', 'couponCode', 'posNote'].forEach(id => $(id).value = ''); $('couponMsg').textContent = ''; customer = walkIn; picker.set(walkIn); render(); loadGrid(); $('posSearch').focus(); if (location.search.includes('edit=')) history.replaceState(null, '', location.pathname); };

    // Pay modal
    const payModal = new bootstrap.Modal($('payModal')), doneModal = new bootstrap.Modal($('doneModal'));
    const payRow = (method = methods[0], amount = '') => {
        $('payRows').insertAdjacentHTML('beforeend', `<div class="pay-row"><select class="form-select pm">${methods.map(m => `<option ${m === method ? 'selected' : ''}>${esc(m)}</option>`).join('')}</select>
            <input type="number" step="any" min="0" class="form-control pa" placeholder="Amount" value="${amount}"><button type="button" class="btn btn-light" title="Cheque / ref"><i class="bi bi-three-dots"></i></button>
            <div class="chq"><input class="form-control form-control-sm pc" placeholder="Cheque / reference no."></div></div>`);
        const row = $('payRows').lastElementChild; row.querySelector('button').onclick = () => row.classList.toggle('has-chq'); row.querySelector('.pa').addEventListener('input', paySum); row.querySelector('.pa').focus();
    };
    const payRowsData = () => [...document.querySelectorAll('#payRows .pay-row')].map(r => ({ method: r.querySelector('.pm').value, amount: num(r.querySelector('.pa').value), cheque_no: r.querySelector('.pc').value || null })).filter(p => p.amount > 0);
    const paySum = () => {
        const g = totals().grand, paid = payRowsData().reduce((s, p) => s + p.amount, 0);
        $('mTotal').textContent = fx(g); $('mPaid').textContent = fx(paid); $('mChange').textContent = fx(Math.max(0, paid - g)); $('mDue').textContent = fx(Math.max(0, g - paid));
    };
    const needReg = () => { if (NEED_REG) { toast('Open a cash register before selling.', false, 'Register required'); if (REG_URL) setTimeout(() => location.href = REG_URL, 900); return true; } return false; };
    const openPay = () => {
        if (needReg()) return;
        if (!cart.length) return toast('Add at least one item first.', false, 'Cart is empty');
        const g = totals().grand; $('payRows').innerHTML = ''; payRow(methods[0], Math.round(g * 100) / 100);
        const notes = [10, 50, 100, 500, 1000, 5000].filter(n => n >= g * 0.2);
        $('quickCash').innerHTML = `<button type="button" class="btn btn-sm btn-outline-primary" data-v="${Math.round(g * 100) / 100}">Exact</button>` + [Math.ceil(g / 100) * 100, Math.ceil(g / 500) * 500, Math.ceil(g / 1000) * 1000, ...notes].filter((v, i, a) => v > g && a.indexOf(v) === i).slice(0, 5).map(v => `<button type="button" class="btn btn-sm btn-outline-secondary" data-v="${v}">${v.toLocaleString()}</button>`).join('');
        $('quickCash').onclick = e => { const b = e.target.closest('[data-v]'); if (b) { document.querySelector('#payRows .pa').value = b.dataset.v; paySum(); } };
        $('mAdvance').textContent = customer && +customer.advance ? `Customer advance available: ${fx(customer.advance)} (choose "Advance").` : (customer?.is_default ? 'Walk-in customer: full payment required.' : (customer && +customer.due ? `Previous due: ${fx(customer.due)}` : ''));
        paySum(); payModal.show(); setTimeout(() => document.querySelector('#payRows .pa')?.select(), 300);
    };
    $('addPayRow').onclick = () => payRow(methods[1] || methods[0], '');
    $('btnPay').onclick = openPay;
    $('btnCash').onclick = () => { if (!cart.length) return toast('Add at least one item first.', false, 'Cart is empty'); $('payRows').innerHTML = ''; payRow(methods.includes('Cash') ? 'Cash' : methods[0], Math.round(totals().grand * 100) / 100); paySum(); checkout(); };
    const checkout = async () => {
        const btn = $('confirmPay'); btn.disabled = true;
        try {
            const j = await post(URL.store, payload(payRowsData()));
            payModal.hide(); $('heldCount') && ($('heldCount').textContent = j.held);
            $('doneNo').textContent = j.number + ' · ' + CUR + ' ' + fx(j.grand_total);
            $('doneChange').textContent = j.change > 0 ? 'Change: ' + CUR + ' ' + fx(j.change) : (j.grand_total - j.paid > 0.004 ? 'Credit: ' + CUR + ' ' + fx(j.grand_total - j.paid) : '');
            $('doneReceipt').href = j.receipt; $('doneA4').href = j.a4;
            if ($('autoPrint').checked) window.open(j.receipt, '_blank', 'width=420,height=700');
            reset(); doneModal.show();
        } catch (e) { toast(e.message, false, /stock/i.test(e.message) ? 'Out of stock' : 'Sale not completed'); } finally { btn.disabled = false; }
    };
    $('confirmPay').onclick = checkout;

    // Hold
    const heldModal = $('heldModal') ? new bootstrap.Modal($('heldModal')) : null;
    $('btnHold')?.addEventListener('click', async () => {
        if (!cart.length) return toast('Add at least one item first.', false, 'Cart is empty');
        const ref = prompt('Hold reference (optional)', holdRef || ''); if (ref === null) return; holdRef = ref;
        try { const j = await post(URL.hold, payload()); $('heldCount').textContent = j.held; toast('Saved as ' + j.number + '.', true, 'Invoice on hold'); reset(); } catch (e) { toast(e.message, false, 'Could not hold'); }
    });
    const loadDoc = d => {
        cart = []; holdId = d.type === 'hold' ? d.id : null; editId = d.type === 'invoice' ? d.id : null; holdRef = d.ref || null;
        if (d.customer) { customer = d.customer; picker.set(d.customer); }
        $('posWh').value = d.warehouse_id; if ($('posSm')) $('posSm').value = d.salesman_id || '';
        $('otherIn').value = +d.other_charges_input || ''; $('discIn').value = +d.discount_input || ''; $('discType').value = d.discount_type || 'percentage'; $('posNote').value = d.note || '';
        d.lines.forEach(l => add(l, l));
        if (d.coupon_code) { $('couponCode').value = d.coupon_code; $('applyCoupon').click(); }
        render();
    };
    const openHeld = async () => {
        const rows = await fetch(URL.holds, { headers: { Accept: 'application/json' } }).then(r => r.json());
        $('heldRows').innerHTML = rows.length ? rows.map(h => `<tr><td>${esc(h.number)}</td><td>${esc(h.ref || '')}</td><td>${esc(h.customer)}</td><td>${h.items}</td><td class="text-end">${fx(h.total)}</td><td class="small">${esc(h.time)}</td>
            <td class="text-end text-nowrap"><button class="btn btn-sm btn-primary" data-load="${h.id}">Resume</button> <button class="btn btn-sm btn-light text-danger" data-del="${h.id}"><i class="bi bi-trash"></i></button></td></tr>`).join('')
            : '<tr><td colspan="7" class="text-center text-body-secondary py-4">No held invoices.</td></tr>';
        heldModal.show();
    };
    $('btnHeld')?.addEventListener('click', openHeld);
    $('heldRows')?.addEventListener('click', async e => {
        const l = e.target.closest('[data-load]'), d = e.target.closest('[data-del]');
        if (l) { if (cart.length && !confirm('Replace the current cart?')) return; const doc = await fetch(URL.hold1 + l.dataset.load, { headers: { Accept: 'application/json' } }).then(r => r.json()); loadDoc(doc); heldModal.hide(); }
        if (d && confirm('Delete this held invoice?')) { const j = await post(URL.hold1 + d.dataset.del, null, 'DELETE'); $('heldCount').textContent = j.held; openHeld(); }
    });
    $('btnClear').onclick = () => { if (!cart.length || confirm('Clear the cart?')) { cart.forEach(l => logVoid(l, 'Cart cleared')); reset(); } };

    // Keyboard
    document.addEventListener('keydown', e => {
        if (e.key === 'F2') { e.preventDefault(); $('posSearch').focus(); }
        if (e.key === 'F4') { e.preventDefault(); openPay(); }
        if (e.key === 'F8') { e.preventDefault(); $('btnHold')?.click(); }
        if (e.key === 'F9') { e.preventDefault(); $('btnHeld')?.click(); }
        if (e.key === 'Enter' && $('payModal').classList.contains('show') && e.target.tagName !== 'TEXTAREA') { e.preventDefault(); checkout(); }
    });

    @if ($editing) loadDoc(@json($editing)); @endif
    render(); loadGrid(); $('posSearch').focus();
})();
</script>
@endpush
