@extends('layouts.admin.main')
@section('title', 'Billing & Plans')
@section('subtitle', 'Your subscription')

@php
    $fmt = fn ($cur, $amt) => $cur.' '.number_format((float) $amt, 2);
@endphp

@section('content')
<div class="app-content">
    <x-admin.page-header title="Billing & Plans" subtitle="Choose the plan that fits your business. Upgrade or downgrade any time." />

    <x-admin.alerts />

    {{-- Current subscription / trial status --}}
    @if ($active)
        <div class="app-card mb-3" style="border-left:4px solid {{ $active->onTrial() ? '#f59e0b' : '#22c55e' }}">
            <div class="app-card__body d-flex flex-wrap align-items-center gap-3 justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <span class="app-tile__icon tone-{{ $active->onTrial() ? 'warning' : 'success' }}"><i class="bi bi-{{ $active->onTrial() ? 'hourglass-split' : 'check-circle' }}"></i></span>
                    <div>
                        <div class="fw-semibold">
                            @if ($active->onTrial())
                                You're on a free trial
                            @else
                                Current plan: {{ $active->plan?->name ?? 'Custom' }} <span class="text-body-secondary small">({{ ucfirst((string) $active->billing_cycle) }})</span>
                            @endif
                        </div>
                        <div class="small text-body-secondary">
                            @if ($active->ends_at)
                                {{ $active->isActive() ? 'Renews / ends' : 'Ended' }} on {{ $active->ends_at->format('d M Y') }} · {{ $active->daysLeft() }} day(s) left
                            @endif
                            @if ($active->is_recurring) · <span class="text-success">auto-renew on</span>@elseif (! $active->onTrial()) · auto-renew off @endif
                        </div>
                    </div>
                </div>
                @if (! $active->onTrial() && $active->is_recurring)
                    <form method="POST" action="{{ route('billing.cancel') }}" onsubmit="return confirm('Turn off auto-renew? Your plan stays active until the period ends.')">
                        @csrf
                        <x-admin.button variant="light" icon="x-circle">Turn off auto-renew</x-admin.button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @unless ($anyReady)
        <div class="app-alert app-alert--info mb-3"><i class="bi bi-info-circle-fill"></i>
            <div>Online payments aren't enabled yet. Please contact us to activate your plan.</div>
        </div>
    @endunless

    {{-- Billing cycle toggle --}}
    <div class="d-flex justify-content-center mb-4">
        <div class="cycle-toggle" role="group" aria-label="Billing cycle">
            <button type="button" class="cycle-btn is-active" data-cycle="monthly">Monthly</button>
            <button type="button" class="cycle-btn" data-cycle="yearly">Yearly <span class="cycle-save">save more</span></button>
        </div>
    </div>

    {{-- Plans --}}
    <div class="row g-3 plans-grid">
        @forelse ($plans as $plan)
            @php
                $isCurrent = $active && ! $active->onTrial() && $active->plan_id === $plan->id;
                $save = $plan->yearlySavingPercent();
            @endphp
            <div class="col-md-6 col-xl-4">
                <div class="app-card plan-card h-100 {{ $isCurrent ? 'is-current' : '' }}">
                    <div class="app-card__body d-flex flex-column h-100">
                        @if ($isCurrent)<span class="plan-ribbon">Current plan</span>@endif
                        <h3 class="plan-name">{{ $plan->name }}</h3>
                        @if ($plan->description)<p class="plan-desc">{{ $plan->description }}</p>@endif

                        <div class="plan-price" data-monthly="{{ $fmt($plan->currency, $plan->price_monthly) }}" data-yearly="{{ $fmt($plan->currency, $plan->price_yearly) }}">
                            <span class="plan-price__amount">{{ $fmt($plan->currency, $plan->price_monthly) }}</span>
                            <span class="plan-price__per" data-monthly="/month" data-yearly="/year">/month</span>
                        </div>
                        @if ($save > 0)<div class="plan-save {{ '' }}" data-save="1" hidden>Save {{ $save }}% vs monthly</div>@endif

                        <ul class="plan-modules">
                            @foreach ($plan->moduleNames() as $name)
                                <li><i class="bi bi-check2"></i>{{ $name }}</li>
                            @endforeach
                        </ul>

                        <div class="mt-auto">
                            @if ($isCurrent)
                                <button class="btn btn-light w-100" disabled>Your current plan</button>
                            @else
                                <form method="POST" action="{{ route('billing.subscribe') }}" class="subscribe-form">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    <input type="hidden" name="cycle" value="monthly" class="cycle-input">

                                    @if ($anyReady)
                                        <div class="pay-row">
                                            @if ($stripeReady)
                                                <label class="pay-opt"><input type="radio" name="gateway" value="stripe" checked><span><i class="bi bi-credit-card"></i> Card</span></label>
                                            @endif
                                            @if ($paypalReady)
                                                <label class="pay-opt"><input type="radio" name="gateway" value="paypal" @unless($stripeReady) checked @endunless><span><i class="bi bi-paypal"></i> PayPal</span></label>
                                            @endif
                                        </div>
                                        <label class="app-check mb-2">
                                            <input type="hidden" name="auto_renew" value="0">
                                            <input type="checkbox" name="auto_renew" value="1" class="form-check-input" checked>
                                            <span>Auto-renew (recurring) — turn off for a one-time payment</span>
                                        </label>
                                        <button type="submit" class="btn btn-primary w-100">Subscribe</button>
                                    @else
                                        <button type="button" class="btn btn-light w-100" disabled>Payments not enabled</button>
                                    @endif
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><x-admin.empty icon="box-seam" text="No plans are available yet. Please check back soon." /></div>
        @endforelse
    </div>
</div>
@endsection

@push('styles')
<style>
    .cycle-toggle { display:inline-flex; background:var(--app-surface-2,#f1f5f9); border:1px solid var(--app-border); border-radius:999px; padding:4px; gap:4px; }
    .cycle-btn { border:0; background:transparent; padding:.5rem 1.4rem; border-radius:999px; font-weight:600; font-size:.9rem; color:var(--app-text-muted); cursor:pointer; transition:all .15s; }
    .cycle-btn.is-active { background:var(--app-primary,#4f46e5); color:#fff; box-shadow:0 2px 8px rgba(79,70,229,.3); }
    .cycle-save { font-size:.7rem; background:#22c55e; color:#fff; padding:.1rem .4rem; border-radius:999px; margin-left:.3rem; }
    .plan-card { position:relative; border:1.5px solid var(--app-border); transition:all .18s; }
    .plan-card:hover { border-color:#a5b4fc; transform:translateY(-2px); }
    .plan-card.is-current { border-color:#22c55e; }
    .plan-ribbon { position:absolute; top:0; right:0; background:#22c55e; color:#fff; font-size:.68rem; font-weight:700; padding:.25rem .7rem; border-radius:0 12px 0 12px; }
    .plan-name { font-size:1.2rem; font-weight:700; margin-bottom:.25rem; color:var(--app-text); }
    .plan-desc { font-size:.85rem; color:var(--app-text-muted); min-height:2.2em; }
    .plan-price { margin:.6rem 0 .2rem; }
    .plan-price__amount { font-size:1.9rem; font-weight:800; color:var(--app-text); }
    .plan-price__per { font-size:.85rem; color:var(--app-text-muted); }
    .plan-save { font-size:.75rem; color:#16a34a; font-weight:600; margin-bottom:.4rem; }
    .plan-modules { list-style:none; padding:0; margin:1rem 0; display:grid; gap:.4rem; }
    .plan-modules li { font-size:.875rem; color:var(--app-text); display:flex; align-items:center; gap:.5rem; }
    .plan-modules li i { color:#22c55e; }
    .pay-row { display:flex; gap:.5rem; margin-bottom:.6rem; }
    .pay-opt { flex:1; }
    .pay-opt input { position:absolute; opacity:0; }
    .pay-opt span { display:flex; align-items:center; justify-content:center; gap:.4rem; padding:.5rem; border:1.5px solid var(--app-border); border-radius:9px; font-size:.85rem; cursor:pointer; font-weight:600; color:var(--app-text-muted); }
    .pay-opt input:checked + span { border-color:var(--app-primary,#4f46e5); color:var(--app-primary,#4f46e5); background:rgba(99,102,241,.07); }
    .app-check { display:flex; align-items:flex-start; gap:.5rem; font-size:.8rem; color:var(--app-text-muted); cursor:pointer; }
    .app-check .form-check-input { margin-top:.1rem; flex-shrink:0; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    let cycle = 'monthly';
    const btns = document.querySelectorAll('.cycle-btn');
    const apply = () => {
        btns.forEach(b => b.classList.toggle('is-active', b.dataset.cycle === cycle));
        document.querySelectorAll('.plan-price').forEach(p => {
            p.querySelector('.plan-price__amount').textContent = p.dataset[cycle];
            const per = p.querySelector('.plan-price__per');
            per.textContent = per.dataset[cycle];
        });
        document.querySelectorAll('.plan-save[data-save]').forEach(s => { s.hidden = cycle !== 'yearly'; });
        document.querySelectorAll('.cycle-input').forEach(i => { i.value = cycle; });
    };
    btns.forEach(b => b.addEventListener('click', () => { cycle = b.dataset.cycle; apply(); }));
    apply();
})();
</script>
@endpush
