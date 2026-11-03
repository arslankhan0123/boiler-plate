{{-- Searchable select (combobox). Same API as before:
     <x-admin.select name="role" label="Role" :options="['a'=>'A']" :value="..." placeholder="Select"/>
     Submits via a hidden input named {{ name }}; fires a native `change` on it so
     onchange="this.form.submit()" style usages keep working. --}}
@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'required' => false, 'optional' => null])
@php
    $id = 'f_'.$name;
    $selected = old($name, $value);
    $ph = $placeholder ?: 'Select…';
    $selLabel = '';
    foreach ($options as $k => $t) {
        if ((string) $selected === (string) $k) { $selLabel = $t; break; }
    }
    $hasValue = $selLabel !== '';
@endphp

@once
@push('styles')
<style>
/* ── Searchable select (global) ── */
.ss { position: relative; }
.ss-display { cursor: pointer; background: var(--app-surface, #fff) !important; }
.ss-display:read-only { background: var(--app-surface, #fff); }
.ss::after { content: "\F282"; font-family: "bootstrap-icons"; position: absolute; right: .75rem; top: 50%; transform: translateY(-50%); color: var(--app-text-muted, #9aa3b2); pointer-events: none; font-size: .8rem; transition: transform .2s; }
.ss.is-open::after { transform: translateY(-50%) rotate(180deg); }
.ss-menu { position: absolute; z-index: 60; left: 0; right: 0; top: calc(100% + 4px); background: var(--app-surface, #fff); border: 1px solid var(--app-border, rgba(0,0,0,.12)); border-radius: 10px; box-shadow: 0 12px 32px rgba(17,24,39,.18); overflow: hidden; }
.ss-menu[hidden] { display: none; }
.ss-search-wrap { padding: .5rem; border-bottom: 1px solid var(--app-border, rgba(0,0,0,.08)); }
.ss-options { max-height: 240px; overflow: auto; padding: .3rem; }
.ss-opt { padding: .45rem .6rem; border-radius: 7px; cursor: pointer; font-size: .875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--app-text, #1e293b); }
.ss-opt:hover, .ss-opt.ss-active { background: rgba(99,102,241,.12); }
.ss-opt.ss-selected { font-weight: 600; color: var(--app-primary, #4f46e5); }
.ss-empty { padding: .7rem; color: var(--app-text-muted, #9aa3b2); font-size: .85rem; text-align: center; }
</style>
@endpush
@endonce

<div class="app-field">
    @if ($label)<label for="{{ $id }}_d" class="form-label">{{ $label }}@if ($required)<span class="req">*</span>@endif @if ($optional)<span class="opt">{{ $optional === true ? '(optional)' : $optional }}</span>@endif</label>@endif
    <div class="ss" data-ss-select>
        <input type="hidden" id="{{ $id }}" name="{{ $name }}" value="{{ $selected }}" {{ $attributes->except(['class', 'id']) }}>
        <input type="text" id="{{ $id }}_d" class="form-control ss-display {{ $errors->has($name) ? 'is-invalid' : '' }}" readonly value="{{ $selLabel }}" placeholder="{{ $ph }}" @if($required) aria-required="true" @endif>
        <div class="ss-menu" hidden>
            <div class="ss-search-wrap"><input type="text" class="form-control form-control-sm ss-search" placeholder="Search…" autocomplete="off"></div>
            <div class="ss-options">
                @if ($placeholder)<div class="ss-opt {{ ! $hasValue ? 'ss-selected' : '' }}" data-value="" data-label="{{ $ph }}">{{ $ph }}</div>@endif
                @foreach ($options as $key => $text)
                    <div class="ss-opt {{ (string) $selected === (string) $key ? 'ss-selected' : '' }}" data-value="{{ $key }}" data-label="{{ $text }}">{{ $text }}</div>
                @endforeach
            </div>
        </div>
    </div>
    @error($name)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @if ($hint)<div class="form-text">{{ $hint }}</div>@endif
</div>

@once
@push('scripts')
<script>
(function(){
  function initSS(ss){
    if(ss.dataset.ssReady) return; ss.dataset.ssReady='1';
    const hidden=ss.querySelector('input[type=hidden]');
    const display=ss.querySelector('.ss-display');
    const menu=ss.querySelector('.ss-menu');
    const search=ss.querySelector('.ss-search');
    const optWrap=ss.querySelector('.ss-options');
    const opts=Array.from(ss.querySelectorAll('.ss-opt'));
    const empty=document.createElement('div'); empty.className='ss-empty'; empty.textContent='No matches'; empty.hidden=true; optWrap.appendChild(empty);
    const vis=()=>opts.filter(o=>o.style.display!=='none');
    function filter(q){ q=(q||'').toLowerCase(); let n=0; opts.forEach(o=>{ const m=(o.dataset.label||'').toLowerCase().includes(q); o.style.display=m?'':'none'; if(m)n++; }); empty.hidden=n>0; }
    function setActive(el){ opts.forEach(o=>o.classList.remove('ss-active')); if(!el)return; el.classList.add('ss-active'); el.scrollIntoView({block:'nearest'}); }
    function activeEl(){ return ss.querySelector('.ss-opt.ss-active'); }
    function open(){ if(display.disabled) return; menu.hidden=false; ss.classList.add('is-open'); search.value=''; filter(''); const sel=opts.find(o=>o.classList.contains('ss-selected')&&o.style.display!=='none'); setActive(sel||vis()[0]); setTimeout(()=>search.focus(),0); }
    function close(){ menu.hidden=true; ss.classList.remove('is-open'); }
    function choose(o){ hidden.value=o.dataset.value; display.value=(o.dataset.value===''?'':o.dataset.label); opts.forEach(x=>x.classList.remove('ss-selected')); o.classList.add('ss-selected'); close(); hidden.dispatchEvent(new Event('change',{bubbles:true})); }
    display.addEventListener('click',()=>menu.hidden?open():close());
    display.addEventListener('keydown',e=>{ if(['Enter',' ','ArrowDown'].includes(e.key)){ e.preventDefault(); open(); } });
    search.addEventListener('input',()=>{ filter(search.value); setActive(vis()[0]); });
    search.addEventListener('keydown',e=>{
      const v=vis(); let i=v.indexOf(activeEl());
      if(e.key==='ArrowDown'){ e.preventDefault(); setActive(v[Math.min(v.length-1,i+1)]||v[0]); }
      else if(e.key==='ArrowUp'){ e.preventDefault(); setActive(v[Math.max(0,i-1)]||v[0]); }
      else if(e.key==='Enter'){ e.preventDefault(); const a=activeEl(); if(a) choose(a); }
      else if(e.key==='Escape'){ e.preventDefault(); close(); display.focus(); }
    });
    opts.forEach(o=>o.addEventListener('click',()=>choose(o)));
    document.addEventListener('click',e=>{ if(!ss.contains(e.target)) close(); });
  }
  function initAll(root){ (root||document).querySelectorAll('[data-ss-select]').forEach(initSS); }
  if(document.readyState!=='loading') initAll(); else document.addEventListener('DOMContentLoaded',()=>initAll());
  window.initSearchSelects=initAll;
})();
</script>
@endpush
@endonce
