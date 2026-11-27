@extends('layouts.admin.main')
@php
    $editing = $role->exists;
    $checked = old('permissions', $assigned);
    $me = auth()->user();
    // Modules available in this context, with only the permissions the editor holds.
    $modules = collect(\App\Support\ModuleRegistry::all())
        ->filter(fn ($m, $code) => \App\Support\ModuleAccess::moduleAvailable($me, $code))
        ->map(function ($m) use ($me) { $m['permissions'] = array_filter($m['permissions'], fn ($l, $p) => $me->can($p), ARRAY_FILTER_USE_BOTH); return $m; })
        ->filter(fn ($m) => $m['permissions']);
    $groups = \App\Support\ModuleRegistry::groups();
@endphp
@section('title', $editing ? 'Edit Role' : 'Create Role')
@section('subtitle', 'Define a role and its module permissions')

@section('content')
<div class="app-content">
    <x-admin.page-header :back="route('roles.index')" :title="$editing ? 'Edit Role' : 'Create Role'"
        subtitle="Name the role and choose what it can do in each module">
        <x-admin.button variant="light" :href="route('roles.index')" icon="list-ul">All Roles</x-admin.button>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('roles.update', $role) : route('roles.store') }}" class="app-form-grid">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="app-card app-form-card">
            <x-admin.input name="name" label="Role Name" :value="$role->name" placeholder="e.g. Store Manager, Cashier, Accountant" required />

            <div class="app-field">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <label class="form-label mb-0">Permissions <span class="opt">only modules enabled for your organization are listed</span></label>
                    <label class="d-flex align-items-center gap-2 mb-0 small fw-semibold" style="cursor:pointer">
                        <input class="form-check-input mt-0" type="checkbox" id="checkAll"> Select all
                    </label>
                </div>

                @foreach ($modules->groupBy('group', true) as $group => $mods)
                    <div class="form-section-title mt-3">{{ $groups[$group] ?? $group }}</div>
                    <div class="d-grid gap-3">
                        @foreach ($mods as $code => $m)
                            <div class="perm-card" data-module="{{ $code }}" data-view="{{ $m['view_permission'] }}">
                                <div class="perm-card__head">
                                    <span class="app-tile__icon tone-primary"><i class="bi {{ $m['icon'] }}"></i></span>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $m['name'] }}</div>
                                        <div class="small text-body-secondary"><span class="perm-count">0</span> of {{ count($m['permissions']) }} selected</div>
                                    </div>
                                    <label class="d-flex align-items-center gap-2 mb-0 small fw-semibold" style="cursor:pointer">
                                        <input class="form-check-input mt-0 row-toggle" type="checkbox"> All
                                    </label>
                                </div>
                                <div class="perm-card__body">
                                    @foreach ($m['permissions'] as $perm => $label)
                                        <label class="mod-feature">
                                            <input class="form-check-input mt-0 perm" type="checkbox" name="permissions[]" value="{{ $perm }}" @checked(in_array($perm, $checked))>
                                            <span>{{ $label }} <code class="perm-code">{{ $perm }}</code></span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="app-form-actions">
                <x-admin.button class="btn-lg-app" icon="check2">{{ $editing ? 'Save Changes' : 'Create Role' }}</x-admin.button>
                <x-admin.button variant="light" class="btn-lg-app" :href="route('roles.index')">Cancel</x-admin.button>
            </div>
        </div>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Summary</h3>
                <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-body-secondary">Permissions selected</span><strong id="permCount">0</strong></div>
                <div class="d-flex justify-content-between py-2"><span class="text-body-secondary">Modules visible</span><strong id="modCount">0</strong></div>
            </div></div>

            <x-admin.tips :items="[
                'Give each role only the access it really needs.',
                'Ticking any action also ticks that module\'s View permission.',
                'Unticking View removes the whole module from the role.',
                'Users get the new permissions on their next page load.',
            ]" />
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .perm-card { border: 1.5px solid var(--app-border); border-radius: 12px; background: var(--app-surface); }
    .perm-card.has-any { border-color: #a5b4fc; }
    .perm-card__head { display: flex; align-items: center; gap: .9rem; padding: .85rem 1rem; border-bottom: 1px solid var(--app-border); }
    .perm-card__body { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: .6rem 1rem; padding: 1rem; }
    .mod-feature { display: flex; align-items: center; gap: .5rem; font-size: .85rem; margin: 0; cursor: pointer; }
    .perm-code { font-size: .7rem; color: var(--app-text-muted); background: var(--app-hover); padding: 1px 5px; border-radius: 4px; margin-left: 2px; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const cards = [...document.querySelectorAll('.perm-card')];
    const all = document.getElementById('checkAll');
    const permsOf = c => [...c.querySelectorAll('.perm')];
    const sync = () => {
        let total = 0, mods = 0;
        cards.forEach(c => {
            const p = permsOf(c), on = p.filter(x => x.checked).length;
            c.querySelector('.perm-count').textContent = on;
            c.querySelector('.row-toggle').checked = on === p.length;
            c.classList.toggle('has-any', on > 0);
            total += on; if (on) mods++;
        });
        const every = cards.flatMap(permsOf);
        all.checked = every.length && every.every(x => x.checked);
        const pc = document.getElementById('permCount'), mc = document.getElementById('modCount');
        if (pc) pc.textContent = total; if (mc) mc.textContent = mods;
    };
    all.addEventListener('change', () => { cards.flatMap(permsOf).forEach(x => x.checked = all.checked); sync(); });
    cards.forEach(c => {
        const view = c.dataset.view;
        c.querySelector('.row-toggle').addEventListener('change', e => { permsOf(c).forEach(x => x.checked = e.target.checked); sync(); });
        permsOf(c).forEach(x => x.addEventListener('change', () => {
            const v = permsOf(c).find(p => p.value === view);
            // Any action needs the module's view permission; removing view removes the module.
            if (x.checked && v && x !== v) v.checked = true;
            if (!x.checked && x === v) permsOf(c).forEach(p => p.checked = false);
            sync();
        }));
    });
    sync();
})();
</script>
@endpush
