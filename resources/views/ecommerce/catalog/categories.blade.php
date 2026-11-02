@extends('layouts.admin.main')
@section('title', 'Categories')
@section('subtitle', 'E-commerce')
@section('content')
<style>
    .cat-row{cursor:grab}
    .cat-row.dragging{opacity:.45}
    .cat-drop{outline:2px dashed var(--app-primary,#6366f1);outline-offset:-2px;background:rgba(99,102,241,.08)}
    .cat-toplevel{border:2px dashed transparent;border-radius:10px;transition:all .12s;color:var(--bs-secondary-color,#64748b)}
    .cat-toplevel.cat-drop{border-color:var(--app-primary,#6366f1);color:var(--app-primary,#6366f1);background:rgba(99,102,241,.08)}
    .cat-grip{color:#aeb6c5;margin-right:.35rem}
    .ss{position:relative}
    .ss-display{cursor:pointer}
    .ss-display::after{content:"\F282";font-family:"bootstrap-icons";position:absolute;right:.75rem;top:50%;transform:translateY(-50%);color:#9aa3b2;pointer-events:none;font-size:.8rem}
    .ss-menu{position:absolute;z-index:30;left:0;right:0;top:calc(100% + 4px);background:var(--app-surface,#fff);border:1px solid var(--app-border,rgba(0,0,0,.12));border-radius:10px;box-shadow:0 12px 32px rgba(17,24,39,.14);overflow:hidden}
    .ss-menu[hidden]{display:none}
    .ss-search-wrap{padding:.5rem}
    .ss-options{max-height:240px;overflow:auto;padding:.25rem}
    .ss-opt{padding:.45rem .6rem;border-radius:7px;cursor:pointer;font-size:.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .ss-opt:hover,.ss-opt.ss-active{background:rgba(99,102,241,.12)}
    .ss-opt.ss-selected{font-weight:600;color:var(--app-primary,#4f46e5)}
    .ss-empty{padding:.6rem;color:#9aa3b2;font-size:.85rem;text-align:center}
</style>
<div class="app-content">
    <x-admin.page-header title="Store Categories" :back="route('ecommerce.catalog.index')" />
    <div class="row g-4">
        <div class="col-lg-8"><x-admin.card flush>
            <div class="px-3 pt-3">
                <div class="cat-toplevel text-center small py-2" id="catTopLevel"><i class="bi bi-arrow-up-circle me-1"></i>Drop a category here to move it to the top level</div>
            </div>
            <div class="table-responsive"><table class="table app-table align-middle" id="catTable">
                <thead><tr><th>Name</th><th class="text-center">Products</th><th class="text-end"></th></tr></thead>
                <tbody>
                @forelse ($rows as $row)
                    @php $c = $row['cat']; $depth = $row['depth']; @endphp
                    <tr class="cat-row @if ($editing && $editing->id === $c->id) table-active @endif" draggable="true" data-id="{{ $c->id }}">
                        <td>
                            <span style="display:inline-block;width:{{ $depth * 1.6 }}rem"></span>
                            <i class="bi bi-grip-vertical cat-grip"></i>
                            @if ($depth > 0)<span class="text-body-secondary">↳ </span>@endif
                            <span class="fw-semibold">{{ $c->name }}</span>
                            @if ($c->parent)<span class="small text-body-secondary"> · under {{ $c->parent->name }}</span>@endif
                        </td>
                        <td class="text-center">{{ $c->products_count }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('ecommerce.catalog.categories.edit', $c) }}" class="btn btn-action" title="Edit"><i class="bi bi-pencil-square"></i></a>
                            <x-admin.delete-button :action="route('ecommerce.catalog.categories.destroy', $c)" :confirm="'Delete '.$c->name.'? Its subcategories will become top-level.'" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3"><x-admin.empty icon="tags" text="No categories yet. Add one on the right." /></td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="px-3 pb-3 small text-body-secondary"><i class="bi bi-info-circle me-1"></i>Tip: drag a category onto another to make it a subcategory.</div>
        </x-admin.card></div>

        <div class="col-lg-4"><x-admin.card :title="$editing ? 'Edit category' : 'New category'">
            <form method="POST" action="{{ $editing ? route('ecommerce.catalog.categories.update', $editing) : route('ecommerce.catalog.categories.store') }}">
                @csrf @if ($editing)@method('PUT')@endif
                <x-admin.input name="name" label="Name" :value="$editing?->name" required />
                @php $selParent = $editing?->parent_id; $selLabel = $selParent && isset($parents[$selParent]) ? $parents[$selParent] : '— None (top level) —'; @endphp
                <div class="app-field">
                    <label class="form-label">Parent category</label>
                    <div class="ss" data-ss>
                        <input type="hidden" name="parent_id" value="{{ $selParent }}">
                        <input type="text" class="form-control ss-display" readonly value="{{ $selLabel }}" placeholder="— None (top level) —">
                        <div class="ss-menu" hidden>
                            <div class="ss-search-wrap"><input type="text" class="form-control form-control-sm ss-search" placeholder="Search categories…"></div>
                            <div class="ss-options">
                                <div class="ss-opt {{ ! $selParent ? 'ss-selected' : '' }}" data-value="" data-label="— None (top level) —">— None (top level) —</div>
                                @foreach ($parents as $pid => $plabel)
                                    <div class="ss-opt {{ (string) $selParent === (string) $pid ? 'ss-selected' : '' }}" data-value="{{ $pid }}" data-label="{{ $plabel }}">{{ $plabel }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="form-text">Pick a parent to make this a subcategory</div>
                </div>
                <x-admin.input name="sort" label="Sort order" type="number" :value="$editing?->sort" optional />
                <div class="d-grid gap-2 mt-3">
                    <x-admin.button :icon="$editing ? 'check-lg' : 'plus-lg'">{{ $editing ? 'Save changes' : 'Add category' }}</x-admin.button>
                    @if ($editing)<x-admin.button variant="light" :href="route('ecommerce.catalog.categories')" type="button">Cancel</x-admin.button>@endif
                </div>
            </form>
        </x-admin.card></div>
    </div>
</div>

<script>
(function () {
    const token = '{{ csrf_token() }}';
    const base = '{{ url('ecommerce/catalog/categories') }}';
    let draggedId = null;

    function reparent(id, parentId) {
        fetch(base + '/' + id + '/reparent', {
            method: 'PATCH',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
            body: JSON.stringify({parent_id: parentId})
        }).then(r => r.json().then(j => ({ok: r.ok, j})))
          .then(({ok, j}) => { if (ok && j.ok) { location.reload(); } else { alert(j.message || 'Could not move the category.'); } })
          .catch(() => alert('Could not move the category.'));
    }

    document.querySelectorAll('.cat-row').forEach(row => {
        row.addEventListener('dragstart', e => { draggedId = row.dataset.id; row.classList.add('dragging'); e.dataTransfer.effectAllowed = 'move'; });
        row.addEventListener('dragend', () => { draggedId = null; row.classList.remove('dragging'); document.querySelectorAll('.cat-drop').forEach(x => x.classList.remove('cat-drop')); });
        row.addEventListener('dragover', e => { e.preventDefault(); if (row.dataset.id !== draggedId) row.classList.add('cat-drop'); });
        row.addEventListener('dragleave', () => row.classList.remove('cat-drop'));
        row.addEventListener('drop', e => {
            e.preventDefault(); row.classList.remove('cat-drop');
            if (draggedId && row.dataset.id !== draggedId) reparent(draggedId, row.dataset.id);
        });
    });

    const top = document.getElementById('catTopLevel');
    top.addEventListener('dragover', e => { e.preventDefault(); top.classList.add('cat-drop'); });
    top.addEventListener('dragleave', () => top.classList.remove('cat-drop'));
    top.addEventListener('drop', e => { e.preventDefault(); top.classList.remove('cat-drop'); if (draggedId) reparent(draggedId, null); });

    // Searchable parent-category dropdown
    document.querySelectorAll('[data-ss]').forEach(ss => {
        const hidden = ss.querySelector('input[type=hidden]');
        const display = ss.querySelector('.ss-display');
        const menu = ss.querySelector('.ss-menu');
        const search = ss.querySelector('.ss-search');
        const opts = Array.from(ss.querySelectorAll('.ss-opt'));
        const emptyEl = document.createElement('div');
        emptyEl.className = 'ss-empty'; emptyEl.textContent = 'No matches'; emptyEl.hidden = true;
        ss.querySelector('.ss-options').appendChild(emptyEl);

        function openMenu() { menu.hidden = false; search.value = ''; filter(''); search.focus(); }
        function closeMenu() { menu.hidden = true; }
        function filter(q) {
            q = q.toLowerCase(); let shown = 0;
            opts.forEach(o => { const m = o.dataset.label.toLowerCase().includes(q); o.style.display = m ? '' : 'none'; if (m) shown++; });
            emptyEl.hidden = shown > 0;
        }
        display.addEventListener('click', () => menu.hidden ? openMenu() : closeMenu());
        search.addEventListener('input', () => filter(search.value));
        opts.forEach(o => o.addEventListener('click', () => {
            hidden.value = o.dataset.value;
            display.value = o.dataset.label;
            opts.forEach(x => x.classList.remove('ss-selected'));
            o.classList.add('ss-selected');
            closeMenu();
        }));
        document.addEventListener('click', e => { if (!ss.contains(e.target)) closeMenu(); });
    });
})();
</script>
@endsection
