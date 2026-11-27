@extends('layouts.admin.main')
@php
    $editing = $tenant->exists;
    $selected = old('modules', $tenant->exists ? $tenant->enabledModules() : []);
    $offFeatures = $tenant->exists ? $tenant->disabledFeatures() : [];
    $registry = \App\Support\ModuleRegistry::class;
    $byGroup = collect($registry::assignable())->groupBy('group', true);
    $active = (string) old('is_active', $tenant->is_active ? '1' : '0');
@endphp
@section('title', $editing ? 'Edit Tenant' : 'Create Tenant')
@section('subtitle', $editing ? 'Update tenant settings and modules' : 'A new database is created automatically')

@section('content')
<div class="app-content">
    <x-admin.page-header :back="route('tenants.index')" :title="$editing ? 'Edit Tenant' : 'Create Tenant'"
        :subtitle="$editing ? 'Database: '.$tenant->databaseName() : 'Fill in the details — the tenant gets its own database'">
        <x-admin.button variant="light" :href="route('tenants.index')" icon="list-ul">All Tenants</x-admin.button>
    </x-admin.page-header>

    <x-admin.alerts />

    <form method="POST" action="{{ $editing ? route('tenants.update', $tenant) : route('tenants.store') }}" class="app-form-grid">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="app-card app-form-card">
            <x-admin.input name="name" label="Tenant / Company Name" :value="$tenant->name" placeholder="e.g. Acme Retailers" required />

            <div class="app-field">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0">Modules <span class="opt">subscription — what this tenant can use</span></label>
                </div>
                <p class="form-text mt-0 mb-3">Users, Roles &amp; Settings are always included. Required modules are enabled automatically.</p>

                @if ($errors->has('modules'))
                    <div class="app-alert app-alert--danger mb-3"><i class="bi bi-exclamation-circle-fill"></i>
                        <div>@foreach ($errors->get('modules') as $msg)<div>{{ $msg }}</div>@endforeach</div>
                    </div>
                @endif

                @foreach ($byGroup as $group => $mods)
                    <div class="form-section-title mt-3">{{ $registry::groups()[$group] ?? $group }}</div>
                    <div class="d-grid gap-3">
                        @foreach ($mods as $code => $m)
                            @php
                                $on = in_array($code, $selected);
                                $oldFeatures = old("features.$code");
                            @endphp
                            <div class="mod-card {{ $on ? 'is-on' : '' }}" data-module="{{ $code }}"
                                 data-requires='@json($m['requires'])' data-name="{{ $m['name'] }}">
                                <label class="mod-card__head">
                                    <input type="checkbox" class="form-check-input mod-cb" name="modules[]" value="{{ $code }}" @checked($on)>
                                    <span class="app-tile__icon tone-primary"><i class="bi {{ $m['icon'] }}"></i></span>
                                    <span class="flex-grow-1">
                                        <span class="mod-card__name">{{ $m['name'] }}</span>
                                        <span class="mod-card__desc">{{ $m['description'] }}</span>
                                    </span>
                                    @if ($m['requires'])
                                        <span class="mod-card__req"><i class="bi bi-link-45deg"></i>Needs {{ collect($m['requires'])->map(fn ($r) => $registry::get($r)['name'])->join(', ') }}</span>
                                    @elseif ($m['requires_any'])
                                        <span class="mod-card__req"><i class="bi bi-link-45deg"></i>Needs 1 business module</span>
                                    @endif
                                    @if ($m['features'])
                                        <button type="button" class="mod-card__toggle" aria-label="Features"><i class="bi bi-chevron-down"></i></button>
                                    @endif
                                </label>
                                @if ($m['features'])
                                    <div class="mod-card__features">
                                        @foreach ($m['features'] as $fkey => $f)
                                            @php $fOn = is_array($oldFeatures) ? in_array($fkey, $oldFeatures) : ! in_array($fkey, $offFeatures[$code] ?? []); @endphp
                                            <label class="mod-feature">
                                                <input type="checkbox" class="form-check-input mt-0" name="features[{{ $code }}][]" value="{{ $fkey }}" @checked($fOn)>
                                                {{ $f['name'] }}
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            @unless ($editing)
                <div class="form-section-title mt-4">Tenant admin login</div>
                <x-admin.input name="admin_name" label="Admin Name" placeholder="e.g. Ali Khan" required />
                <x-admin.input name="admin_email" type="email" label="Admin Email" placeholder="admin@acme.com" required
                    hint="The tenant admin signs in with this email and sees only the modules above." />
                <div class="row g-4 mt-0">
                    <div class="col-md-6">
                        <x-admin.input name="admin_password" type="password" label="Password" placeholder="Minimum 8 characters" autocomplete="new-password" required />
                    </div>
                    <div class="col-md-6">
                        <x-admin.input name="admin_password_confirmation" type="password" label="Confirm Password" placeholder="Re-enter password" autocomplete="new-password" />
                    </div>
                </div>
            @endunless

            <div class="app-form-actions">
                <x-admin.button class="btn-lg-app" icon="check2">{{ $editing ? 'Save Changes' : 'Create Tenant' }}</x-admin.button>
                <x-admin.button variant="light" class="btn-lg-app" :href="route('tenants.index')">Cancel</x-admin.button>
            </div>
        </div>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Status</h3>
                <x-admin.radio-card name="is_active" value="1" title="Active" desc="Tenant users can sign in" :checked="$active === '1'" badge="Active" tone="success" />
                <x-admin.radio-card name="is_active" value="0" title="Inactive" desc="Sign-in is blocked" :checked="$active === '0'" badge="Inactive" tone="danger" />
            </div></div>

            @if ($editing && $admin)
                <div class="app-card"><div class="app-card__body">
                    <h3 class="app-aside-title">Tenant admin</h3>
                    <div class="d-flex align-items-center gap-3">
                        <span class="app-avatar">{{ mb_substr($admin->name, 0, 1) }}</span>
                        <div><div class="fw-semibold">{{ $admin->name }}</div><div class="small text-body-secondary">{{ $admin->email }}</div></div>
                    </div>
                </div></div>
            @endif

            <x-admin.tips :items="[
                'Creating a tenant makes a new database (e.g. '.config('tenancy.database.prefix').'001) and migrates it.',
                'The tenant admin can create their own roles and users.',
                'Turning a module off hides it instantly for all of that tenant\'s users.',
                'Deleting a tenant permanently drops its database.',
            ]" />
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .mod-card { border: 1.5px solid var(--app-border); border-radius: 12px; transition: all .18s; background: var(--app-surface); }
    .mod-card.is-on { border-color: #818cf8; background: rgba(99,102,241,.04); }
    .mod-card__head { display: flex; align-items: center; gap: .9rem; padding: .9rem 1rem; margin: 0; cursor: pointer; }
    .mod-card__head .form-check-input { margin: 0; flex-shrink: 0; }
    .mod-card__name { display: block; font-weight: 600; color: var(--app-text); }
    .mod-card__desc { display: block; font-size: .8rem; color: var(--app-text-muted); }
    .mod-card__req { font-size: .72rem; font-weight: 600; color: #6366f1; background: rgba(99,102,241,.1); padding: .25rem .55rem; border-radius: 99px; white-space: nowrap; }
    .mod-card__toggle { border: 0; background: none; color: var(--app-text-muted); padding: .3rem .4rem; border-radius: 8px; transition: transform .2s; }
    .mod-card.is-open .mod-card__toggle { transform: rotate(180deg); }
    .mod-card__features { display: none; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: .5rem 1rem;
        padding: .25rem 1rem 1rem 3.4rem; }
    .mod-card.is-open.is-on .mod-card__features { display: grid; }
    .mod-feature { display: flex; align-items: center; gap: .5rem; font-size: .85rem; color: var(--app-text); margin: 0; cursor: pointer; }
    @media (max-width: 767px) { .mod-card__req { display: none; } .mod-card__features { padding-left: 1rem; } }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const cards = [...document.querySelectorAll('.mod-card')];
    const byCode = Object.fromEntries(cards.map(c => [c.dataset.module, c]));
    const cb = c => c.querySelector('.mod-cb');
    const name = code => byCode[code]?.dataset.name || code;
    const reqs = c => JSON.parse(c.dataset.requires || '[]');
    const paint = c => c.classList.toggle('is-on', cb(c).checked);

    cards.forEach(card => {
        const box = cb(card);
        card.querySelector('.mod-card__toggle')?.addEventListener('click', e => {
            e.preventDefault(); card.classList.toggle('is-open');
        });
        box.addEventListener('change', () => {
            const code = card.dataset.module;
            if (box.checked) {
                // Turn on everything this module needs (recursively).
                const added = [];
                const enable = c => reqs(byCode[c]).forEach(r => {
                    if (byCode[r] && !cb(byCode[r]).checked) { cb(byCode[r]).checked = true; paint(byCode[r]); added.push(name(r)); enable(r); }
                });
                enable(code);
                if (added.length) Toast.info(`${added.join(', ')} enabled because ${name(code)} needs it.`, 'Dependencies added');
                card.classList.add('is-open');
            } else {
                // Block turning off a module another enabled module depends on.
                const blockers = cards.filter(c => cb(c).checked && reqs(c).includes(code)).map(c => c.dataset.name);
                if (blockers.length) {
                    box.checked = true;
                    Toast.warning(`Disable ${blockers.join(', ')} first — ${blockers.length > 1 ? 'they need' : 'it needs'} ${name(code)}.`, 'Dependency');
                }
            }
            paint(card);
        });
    });
})();
</script>
@endpush
