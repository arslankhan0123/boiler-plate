@extends('layouts.admin.main')
@php
    $editing = $plan->exists;
    $selected = old('modules', $plan->exists ? $plan->moduleCodes() : []);
    $registry = \App\Support\ModuleRegistry::class;
    $byGroup = collect($registry::assignable())->groupBy('group', true);
    $active = (string) old('is_active', $plan->is_active ? '1' : '0');
    $public = (string) old('is_public', $plan->is_public ?? true ? '1' : '0');
    $gatewaysReady = \App\Support\Billing\PlatformSettings::anyGatewayReady();
@endphp
@section('title', $editing ? 'Edit Plan' : 'Create Plan')
@section('subtitle', 'Subscription plan')

@section('content')
<div class="app-content">
    <x-admin.page-header :back="route('plans.index')" :title="$editing ? 'Edit Plan' : 'Create Plan'"
        subtitle="Pick the modules this plan unlocks and set its prices.">
        <x-admin.button variant="light" :href="route('plans.index')" icon="list-ul">All Plans</x-admin.button>
    </x-admin.page-header>

    <x-admin.alerts />

    <form method="POST" action="{{ $editing ? route('plans.update', $plan) : route('plans.store') }}" class="app-form-grid">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="app-card app-form-card">
            <x-admin.input name="name" label="Plan Name" :value="$plan->name" placeholder="e.g. Starter, Business, Enterprise" required />

            <div class="app-field">
                <label for="f_description" class="form-label">Description <span class="opt">(optional)</span></label>
                <textarea id="f_description" name="description" rows="2" class="form-control" placeholder="Short line shown to tenants on the plans page.">{{ old('description', $plan->description) }}</textarea>
            </div>

            <div class="row g-4">
                <div class="col-md-4"><x-admin.input name="price_monthly" type="number" step="0.01" min="0" label="Monthly Price" :value="old('price_monthly', $plan->price_monthly)" required /></div>
                <div class="col-md-4"><x-admin.input name="price_yearly" type="number" step="0.01" min="0" label="Yearly Price" :value="old('price_yearly', $plan->price_yearly)" required /></div>
                <div class="col-md-4"><x-admin.input name="currency" label="Currency" :value="old('currency', $plan->currency ?: 'USD')" maxlength="3" required hint="3-letter code, e.g. USD, EUR, PKR" /></div>
            </div>

            <div class="app-field">
                <label class="form-label mb-1">Modules in this plan <span class="req">*</span></label>
                <p class="form-text mt-0 mb-3">When a tenant subscribes, exactly these modules are unlocked for them. Required modules are enabled automatically.</p>

                @if ($errors->has('modules'))
                    <div class="app-alert app-alert--danger mb-3"><i class="bi bi-exclamation-circle-fill"></i>
                        <div>@foreach ($errors->get('modules') as $msg)<div>{{ $msg }}</div>@endforeach</div>
                    </div>
                @endif

                @foreach ($byGroup as $group => $mods)
                    <div class="form-section-title mt-3">{{ $registry::groups()[$group] ?? $group }}</div>
                    <div class="d-grid gap-3">
                        @foreach ($mods as $code => $m)
                            @php $on = in_array($code, $selected); @endphp
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
                                    @endif
                                </label>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            @if ($gatewaysReady)
                <label class="app-check mt-3">
                    <input type="checkbox" name="sync_gateways" value="1" class="form-check-input" @checked(old('sync_gateways'))>
                    <span>Also create / update this plan on Stripe &amp; PayPal now (needed for recurring card billing)</span>
                </label>
            @endif

            <div class="app-form-actions">
                <x-admin.button class="btn-lg-app" icon="check2">{{ $editing ? 'Save Plan' : 'Create Plan' }}</x-admin.button>
                <x-admin.button variant="light" class="btn-lg-app" :href="route('plans.index')">Cancel</x-admin.button>
            </div>
        </div>

        <div class="app-aside">
            <div class="app-card"><div class="app-card__body">
                <h3 class="app-aside-title">Availability</h3>
                <x-admin.radio-card name="is_active" value="1" title="Active" desc="Plan can be used" :checked="$active === '1'" badge="Active" tone="success" />
                <x-admin.radio-card name="is_active" value="0" title="Inactive" desc="Hidden everywhere" :checked="$active === '0'" badge="Inactive" tone="danger" />
                <hr class="my-3">
                <h3 class="app-aside-title">Visibility to tenants</h3>
                <x-admin.radio-card name="is_public" value="1" title="Public" desc="Shown on the tenant billing page" :checked="$public === '1'" badge="Public" tone="success" />
                <x-admin.radio-card name="is_public" value="0" title="Hidden" desc="Only you can assign it" :checked="$public === '0'" badge="Hidden" tone="muted" />
            </div></div>

            <div class="app-card"><div class="app-card__body">
                <x-admin.input name="sort_order" type="number" min="0" label="Sort order" :value="old('sort_order', $plan->sort_order ?? 0)" hint="Lower shows first." />
            </div></div>

            <x-admin.tips :items="[
                'A plan is a bundle of modules + a monthly and yearly price.',
                'Yearly price is the full year total (set it below 12× monthly to offer a discount).',
                'Recurring card billing needs the plan synced to Stripe/PayPal.',
                'Make a plan Inactive instead of deleting it if tenants already use it.',
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
    .app-check { display: flex; align-items: flex-start; gap: .6rem; font-size: .9rem; color: var(--app-text); cursor: pointer; }
    .app-check .form-check-input { margin-top: .15rem; flex-shrink: 0; }
    @media (max-width: 767px) { .mod-card__req { display: none; } }
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
        box.addEventListener('change', () => {
            const code = card.dataset.module;
            if (box.checked) {
                const added = [];
                const enable = c => reqs(byCode[c]).forEach(r => {
                    if (byCode[r] && !cb(byCode[r]).checked) { cb(byCode[r]).checked = true; paint(byCode[r]); added.push(name(r)); enable(r); }
                });
                enable(code);
                if (added.length && window.Toast) Toast.info(`${added.join(', ')} enabled because ${name(code)} needs it.`, 'Dependencies added');
            } else {
                const blockers = cards.filter(c => cb(c).checked && reqs(c).includes(code)).map(c => c.dataset.name);
                if (blockers.length) {
                    box.checked = true;
                    if (window.Toast) Toast.warning(`Disable ${blockers.join(', ')} first — ${blockers.length > 1 ? 'they need' : 'it needs'} ${name(code)}.`, 'Dependency');
                }
            }
            paint(card);
        });
    });
})();
</script>
@endpush
