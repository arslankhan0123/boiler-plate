{{--
    Customer / supplier picker. PartyPicker.attach(wrapEl, { url, onPick(party), createUrl, label })
    The wrap must contain: <input type="hidden" data-party-id> and <input data-party-search>.
--}}
@once
@push('styles')
<style>
    .pp { position: relative; }
    .pp-list { position: absolute; z-index: 1060; left: 0; right: 0; top: calc(100% + 4px); background: #fff; border: 1px solid var(--app-border); border-radius: 12px; box-shadow: 0 12px 32px rgba(15,23,42,.12); max-height: 300px; overflow-y: auto; display: none; }
    .pp-row { padding: .55rem .85rem; cursor: pointer; display: flex; justify-content: space-between; gap: .8rem; border-bottom: 1px solid var(--app-border); }
    .pp-row:last-child { border-bottom: 0; } .pp-row:hover, .pp-row.is-active { background: #eef2ff; }
    .pp-row small { color: var(--app-text-muted); }
    .pp-add { padding: .6rem .85rem; color: var(--app-primary, #4f46e5); font-weight: 600; cursor: pointer; }
    .pp-info { display: flex; gap: .4rem; flex-wrap: wrap; margin-top: .35rem; font-size: .75rem; }
    .pp-info span { background: var(--app-hover); border-radius: 6px; padding: .05rem .45rem; }
    .pp-info .due { background: #fef2f2; color: #b91c1c; } .pp-info .adv { background: #ecfdf5; color: #047857; }
</style>
@endpush
@push('scripts')
<script>
window.PartyPicker = {
    attach(wrap, opts) {
        const hidden = wrap.querySelector('[data-party-id]'), input = wrap.querySelector('[data-party-search]'), info = wrap.querySelector('[data-party-info]');
        wrap.classList.add('pp');
        const list = document.createElement('div'); list.className = 'pp-list'; wrap.appendChild(list);
        let rows = [], active = 0, t;
        const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const money = v => (+v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const showInfo = p => {
            if (!info) return;
            info.innerHTML = !p ? '' : [p.mobile ? `<span><i class="bi bi-telephone"></i> ${esc(p.mobile)}</span>` : '',
                +p.due ? `<span class="due">Due ${money(p.due)}</span>` : '', +p.advance ? `<span class="adv">Advance ${money(p.advance)}</span>` : '',
                +p.credit_limit ? `<span>Limit ${money(p.credit_limit)}</span>` : '', p.price_level_type && +p.price_level ? `<span>Price ${p.price_level_type === 'increase' ? '+' : '−'}${+p.price_level}%</span>` : ''].join('');
        };
        const set = p => { hidden.value = p ? p.id : ''; input.value = p ? p.name : ''; list.style.display = 'none'; showInfo(p); opts.onPick?.(p); };
        const render = () => {
            list.innerHTML = rows.map((r, i) => `<div class="pp-row ${i === active ? 'is-active' : ''}" data-i="${i}"><div><b>${esc(r.name)}</b><br><small>${esc(r.mobile || r.code || '')}</small></div>
                <div class="text-end"><small>${+r.due ? 'Due ' + money(r.due) : ''}</small></div></div>`).join('')
                + (opts.createUrl ? `<div class="pp-add" data-add><i class="bi bi-plus-lg"></i> Add "${esc(input.value || 'new')}" as new ${opts.label || 'party'}</div>` : '');
            list.style.display = 'block';
        };
        const search = async () => { const r = await fetch(opts.url + '?q=' + encodeURIComponent(input.value), { headers: { Accept: 'application/json' } }); rows = r.ok ? await r.json() : []; active = 0; render(); };
        input.addEventListener('focus', () => { input.select(); search(); });
        input.addEventListener('input', () => { clearTimeout(t); t = setTimeout(search, 200); });
        input.addEventListener('keydown', e => {
            if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, rows.length - 1); render(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, 0); render(); }
            else if (e.key === 'Enter') { e.preventDefault(); rows[active] && set(rows[active]); }
            else if (e.key === 'Escape') list.style.display = 'none';
        });
        list.addEventListener('mousedown', async e => {
            e.preventDefault();
            if (e.target.closest('[data-add]')) {
                const name = prompt('New ' + (opts.label || 'party') + ' name', input.value); if (!name) return;
                const mobile = prompt('Mobile (optional)') || '';
                const body = new FormData(); body.append('company_name', name); body.append('mobile', mobile); body.append('phone', mobile); body.append('_token', @json(csrf_token()));
                const r = await fetch(opts.createUrl, { method: 'POST', body, headers: { Accept: 'application/json' } });
                if (!r.ok) { const er = await r.json().catch(() => ({})); return (window.Toast?.error ?? alert)(Object.values(er.errors || {})[0]?.[0] || 'Could not add.'); }
                set(await r.json()); window.Toast?.success(name + ' added.'); return;
            }
            const row = e.target.closest('.pp-row'); if (row) set(rows[+row.dataset.i]);
        });
        document.addEventListener('click', e => { if (!wrap.contains(e.target)) list.style.display = 'none'; });
        if (opts.initial) { hidden.value = opts.initial.id; input.value = opts.initial.name; showInfo(opts.initial); }
        return { set };
    },
};
</script>
@endpush
@endonce
