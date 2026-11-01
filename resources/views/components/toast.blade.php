{{--
    Generic toast notifications. Self-contained (no Bootstrap/Tailwind needed),
    so it works on the login page and the admin panel.

    From PHP (shows after redirect):
        return back()->with('toast', ['type' => 'error', 'title' => 'Account inactive', 'message' => '...']);
        return back()->with('success', 'Saved!');     // also: error, warning, info, status

    From JavaScript:
        Toast.success('Saved!');  Toast.error('Failed', 'Title');
        Toast.show({ type: 'warning', title: 'Heads up', message: '...', duration: 6000 });
--}}
@php
    $flash = [];
    if (is_array(session('toast'))) {
        $flash[] = session('toast');
    }
    foreach (['success' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info', 'status' => 'info'] as $key => $type) {
        if (is_string(session($key)) && session($key) !== '') {
            $flash[] = ['type' => $type, 'message' => session($key)];
        }
    }
@endphp

<div id="app-toasts" class="app-toasts" aria-live="polite" aria-atomic="false"></div>

<style>
    .app-toasts{position:fixed;top:20px;right:20px;z-index:2147483000;display:flex;flex-direction:column;gap:12px;width:min(380px,calc(100vw - 32px));pointer-events:none;font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif}
    .app-toast{--c:#4f46e5;--soft:#eef2ff;pointer-events:auto;position:relative;display:flex;gap:12px;align-items:flex-start;padding:14px 14px 16px 16px;background:#fff;border:1px solid #e5e7eb;border-left:4px solid var(--c);border-radius:12px;box-shadow:0 12px 32px -8px rgba(15,23,42,.22),0 2px 6px rgba(15,23,42,.06);overflow:hidden;transform:translateX(calc(100% + 24px));opacity:0;transition:transform .35s cubic-bezier(.21,1.02,.73,1),opacity .35s ease}
    .app-toast.is-in{transform:translateX(0);opacity:1}
    .app-toast.is-out{transform:translateX(calc(100% + 24px));opacity:0}
    .app-toast__icon{flex-shrink:0;width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:var(--soft);color:var(--c)}
    .app-toast__icon svg{width:18px;height:18px}
    .app-toast__body{flex:1;min-width:0;padding-top:1px}
    .app-toast__title{font-size:14px;font-weight:600;color:#0f172a;margin:0 0 2px;line-height:1.35}
    .app-toast__msg{font-size:13px;color:#475569;margin:0;line-height:1.45;word-wrap:break-word}
    .app-toast__close{flex-shrink:0;border:0;background:transparent;color:#94a3b8;cursor:pointer;padding:4px;border-radius:6px;line-height:0}
    .app-toast__close:hover{background:#f1f5f9;color:#0f172a}
    .app-toast__bar{position:absolute;left:0;bottom:0;height:3px;background:var(--c);opacity:.35;width:100%;transform-origin:left}
    .app-toast--success{--c:#059669;--soft:#ecfdf5}
    .app-toast--error{--c:#e11d48;--soft:#fff1f2}
    .app-toast--warning{--c:#d97706;--soft:#fffbeb}
    .app-toast--info{--c:#4f46e5;--soft:#eef2ff}
    [data-bs-theme="dark"] .app-toast{background:#131829;border-color:#232a40}
    [data-bs-theme="dark"] .app-toast__title{color:#e5e7eb}
    [data-bs-theme="dark"] .app-toast__msg{color:#94a3b8}
    @media (max-width:575px){.app-toasts{top:12px;right:16px;left:16px;width:auto}}
    @media (prefers-reduced-motion:reduce){.app-toast{transition:none}}
</style>

<script>
(() => {
    if (window.Toast) return;
    const icons = {
        success: '<path d="M20 6 9 17l-5-5"/>',
        error: '<circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/>',
        warning: '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
        info: '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
    };
    const titles = { success: 'Success', error: 'Something went wrong', warning: 'Warning', info: 'Notice' };
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function show({ type = 'info', title, message = '', duration } = {}) {
        type = icons[type] ? type : 'info';
        duration = duration ?? (type === 'error' || type === 'warning' ? 7000 : 4500);
        const box = document.getElementById('app-toasts');
        if (!box) return;
        const el = document.createElement('div');
        el.className = `app-toast app-toast--${type}`;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');
        el.innerHTML = `
            <div class="app-toast__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">${icons[type]}</svg></div>
            <div class="app-toast__body">
                <p class="app-toast__title">${esc(title || titles[type])}</p>
                ${message ? `<p class="app-toast__msg">${esc(message)}</p>` : ''}
            </div>
            <button type="button" class="app-toast__close" aria-label="Close"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></button>
            <div class="app-toast__bar"></div>`;
        box.appendChild(el);

        const bar = el.querySelector('.app-toast__bar');
        let remaining = duration, start, timer;
        const close = () => { clearTimeout(timer); el.classList.add('is-out'); setTimeout(() => el.remove(), 350); };
        const run = () => {
            start = Date.now(); timer = setTimeout(close, remaining);
            bar.style.transition = `transform ${remaining}ms linear`; bar.style.transform = 'scaleX(0)';
        };
        const pause = () => {
            clearTimeout(timer); remaining -= Date.now() - start;
            const w = bar.getBoundingClientRect().width / el.getBoundingClientRect().width;
            bar.style.transition = 'none'; bar.style.transform = `scaleX(${w})`;
        };
        el.querySelector('.app-toast__close').addEventListener('click', close);
        el.addEventListener('mouseenter', pause);
        el.addEventListener('mouseleave', run);
        requestAnimationFrame(() => { el.classList.add('is-in'); requestAnimationFrame(run); });
        return { close };
    }

    window.Toast = {
        show,
        success: (message, title) => show({ type: 'success', message, title }),
        error: (message, title) => show({ type: 'error', message, title }),
        warning: (message, title) => show({ type: 'warning', message, title }),
        info: (message, title) => show({ type: 'info', message, title }),
    };

    const queued = @json($flash);
    const fire = () => queued.forEach(t => show(t));
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fire) : fire();
})();
</script>
