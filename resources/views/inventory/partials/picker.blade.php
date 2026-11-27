{{--
    Item picker: <input data-item-picker> + ItemPicker.attach(input, { warehouse: () => id, onPick: item => {}, stockOnly })
    Type a name / code, or scan a barcode and press Enter (exact barcode match is picked automatically).
--}}
@once
@push('styles')
<style>
    .ip-wrap { position: relative; }
    .ip-wrap .ip-icon { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); color: var(--app-text-muted); }
    .ip-wrap input { padding-left: 2.4rem; min-height: 46px; }
    .ip-list { position: absolute; z-index: 1050; left: 0; right: 0; top: calc(100% + 4px); background: #fff; border: 1px solid var(--app-border); border-radius: 12px; box-shadow: 0 12px 32px rgba(15,23,42,.12); max-height: 340px; overflow-y: auto; display: none; }
    .ip-item { display: flex; justify-content: space-between; gap: 1rem; padding: .6rem .9rem; cursor: pointer; border-bottom: 1px solid var(--app-border); }
    .ip-item:last-child { border-bottom: 0; }
    .ip-item.is-active, .ip-item:hover { background: #eef2ff; }
    .ip-item small { color: var(--app-text-muted); }
    .ip-empty { padding: .9rem; color: var(--app-text-muted); font-size: .85rem; }
</style>
@endpush
@push('scripts')
<script>
window.ItemPicker = {
    url: @json(route('inventory.items.search')),
    attach(input, opts = {}) {
        const wrap = document.createElement('div'); wrap.className = 'ip-wrap';
        input.parentNode.insertBefore(wrap, input); wrap.appendChild(input);
        wrap.insertAdjacentHTML('afterbegin', '<i class="bi bi-upc-scan ip-icon"></i>');
        const list = document.createElement('div'); list.className = 'ip-list'; wrap.appendChild(list);
        let items = [], active = 0, timer, seq = 0;
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const fmtQ = v => (+v).toLocaleString(undefined, { maximumFractionDigits: 3 });
        const render = () => {
            list.style.display = 'block';
            if (!items.length) { list.innerHTML = '<div class="ip-empty">No matching items.</div>'; return; }
            list.innerHTML = items.map((it, i) => `<div class="ip-item ${i === active ? 'is-active' : ''}" data-i="${i}">
                <div><div class="fw-semibold">${esc(it.name)}</div><small>${esc(it.code)}${it.barcode && it.barcode !== it.code ? ' · ' + esc(it.barcode) : ''}</small></div>
                <div class="text-end"><div class="fw-semibold">${(+(opts.priceField ? it[opts.priceField] : it.sales_price)).toFixed(2)}</div>
                <small>${it.is_service ? 'Service' : 'Stock: ' + fmtQ(it.stock) + (it.unit ? ' ' + esc(it.unit) : '')}</small></div></div>`).join('');
        };
        const close = () => { list.style.display = 'none'; };
        const pick = it => { if (!it) return; close(); input.value = ''; opts.onPick?.(it); input.focus(); };
        const search = async (exact = false) => {
            const q = input.value.trim(), my = ++seq;
            const params = new URLSearchParams({ q, warehouse: opts.warehouse?.() || '', stock_only: opts.stockOnly ? 1 : 0, limit: 20 });
            const res = await fetch(this.url + '?' + params, { headers: { Accept: 'application/json' } });
            if (my !== seq || !res.ok) return;
            items = await res.json(); active = 0;
            const hit = items.find(i => q && (i.barcode === q || i.code === q));
            if (exact && hit) return pick(hit);
            if (exact && items.length === 1) return pick(items[0]);
            render();
        };
        input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => search(), 220); });
        input.addEventListener('focus', () => { if (!input.value) search(); });
        input.addEventListener('keydown', e => {
            if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, items.length - 1); render(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, 0); render(); }
            else if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); list.style.display === 'block' && items[active] && !/^\S+$/.test(input.value.trim()) ? pick(items[active]) : search(true); }
            else if (e.key === 'Escape') close();
        });
        list.addEventListener('mousedown', e => { const el = e.target.closest('.ip-item'); if (el) { e.preventDefault(); pick(items[+el.dataset.i]); } });
        document.addEventListener('click', e => { if (!wrap.contains(e.target)) close(); });
        return { search, close };
    },
};
</script>
@endpush
@endonce
