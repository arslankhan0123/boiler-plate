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
        <div class="app-alert app-alert--info mb-4"><i class="bi bi-info-circle-fill"></i>
            <div>Online payments aren't enabled yet. Please contact us to activate your plan.</div>
        </div>
    @endunless

    {{-- Billing cycle toggle --}}
    <div class="d-flex justify-content-center mb-4">
        <div class="cycle-toggle" role="group" aria-label="Billing cycle">
            <button type="button" class="cycle-btn is-active" data-cycle="monthly">Monthly</button>
            <button type="button" class="cycle-btn" data-cycle="yearly">Yearly<span class="cycle-save">Save more</span></button>
        </div>
    </div>

    {{-- Plans --}}
    <div class="row g-4 justify-content-center plans-grid">
        @forelse ($plans as $plan)
            @php
                $isCurrent = $active && ! $active->onTrial() && $active->plan_id === $plan->id;
                $save = $plan->yearlySavingPercent();
                $mods = $plan->moduleNames();
            @endphp
            <div class="col-md-6 col-xl-4">
                <div class="pricing-card h-100 {{ $isCurrent ? 'is-current' : '' }}">
                    @if ($isCurrent)<span class="pricing-card__ribbon">Current plan</span>@endif

                    <div class="pricing-card__head">
                        <h3 class="pricing-card__name">{{ $plan->name }}</h3>
                        @if ($plan->description)<p class="pricing-card__desc">{{ $plan->description }}</p>@endif
                    </div>

                    <div class="pricing-card__price"
                         data-monthly="{{ $fmt($plan->currency, $plan->price_monthly) }}"
                         data-yearly="{{ $fmt($plan->currency, $plan->price_yearly) }}">
                        <span class="pricing-card__amount">{{ $fmt($plan->currency, $plan->price_monthly) }}</span>
                        <span class="pricing-card__per" data-monthly="per month" data-yearly="per year">per month</span>
                    </div>
                    @if ($save > 0)
                        <div class="pricing-card__save" data-save hidden><i class="bi bi-tag-fill"></i> Save {{ $save }}% when billed yearly</div>
                    @else
                        <div class="pricing-card__save-spacer"></div>
                    @endif

                    <div class="pricing-card__divider"><span>What's included</span></div>

                    <ul class="pricing-card__features">
                        @foreach ($mods as $name)
                            <li><span class="tick"><i class="bi bi-check-lg"></i></span>{{ $name }}</li>
                        @endforeach
                    </ul>

                    <div class="pricing-card__cta">
                        @if ($isCurrent)
                            <button class="btn w-100 pricing-btn pricing-btn--current" disabled><i class="bi bi-check-circle me-1"></i>Your current plan</button>
                        @elseif ($anyReady)
                            <form method="POST" action="{{ route('billing.subscribe') }}" class="subscribe-form">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <input type="hidden" name="cycle" value="monthly" class="cycle-input">

                                <div class="pay-row">
                                    @if ($stripeReady)
                                        <label class="pay-opt"><input type="radio" name="gateway" value="stripe" checked><span><i class="bi bi-credit-card"></i> Card</span></label>
                                    @endif
                                    @if ($paypalReady)
                                        <label class="pay-opt"><input type="radio" name="gateway" value="paypal" @unless($stripeReady) checked @endunless><span><i class="bi bi-paypal"></i> PayPal</span></label>
                                    @endif
                                </div>
                                <label class="auto-renew">
                                    <input type="hidden" name="auto_renew" value="0">
                                    <input type="checkbox" name="auto_renew" value="1" class="form-check-input" checked>
                                    <span>Auto-renew — off for a one-time payment</span>
                                </label>
                                <button type="submit" class="btn w-100 pricing-btn pricing-btn--primary">Choose {{ $plan->name }}</button>
                            </form>
                        @else
                            <button type="button" class="btn w-100 pricing-btn pricing-btn--muted" disabled><i class="bi bi-lock me-1"></i>Contact us to subscribe</button>
                        @endif
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
    /* ── Billing cycle toggle ── */
    .cycle-toggle { display:inline-flex; background:var(--app-surface-2,#eef2f7); border:1px solid var(--app-border); border-radius:999px; padding:5px; gap:5px; }
    .cycle-btn { border:0; background:transparent; padding:.55rem 1.5rem; border-radius:999px; font-weight:600; font-size:.9rem; color:var(--app-text-muted); cursor:pointer; transition:all .18s; display:inline-flex; align-items:center; gap:.45rem; white-space:nowrap; }
    .cycle-btn.is-active { color:#fff; background:linear-gradient(135deg, var(--app-primary,#4f46e5), #6366f1); box-shadow:0 4px 12px rgba(79,70,229,.35); }
    .cycle-save { font-size:.65rem; font-weight:700; background:#22c55e; color:#fff; padding:.12rem .45rem; border-radius:999px; text-transform:uppercase; letter-spacing:.02em; }
    .cycle-btn.is-active .cycle-save { background:rgba(255,255,255,.22); }

    /* ── Pricing card ── */
    .pricing-card { position:relative; display:flex; flex-direction:column; background:var(--app-surface,#fff); border:1.5px solid var(--app-border); border-radius:18px; padding:1.75rem 1.5rem 1.5rem; box-shadow:0 1px 2px rgba(16,24,40,.04); transition:transform .18s, box-shadow .18s, border-color .18s; }
    .pricing-card:hover { transform:translateY(-4px); box-shadow:0 18px 40px rgba(16,24,40,.12); border-color:#c7d2fe; }
    .pricing-card.is-current { border-color:#22c55e; box-shadow:0 10px 30px rgba(34,197,94,.14); }
    .pricing-card__ribbon { position:absolute; top:-12px; left:50%; transform:translateX(-50%); background:#22c55e; color:#fff; font-size:.7rem; font-weight:700; padding:.3rem .9rem; border-radius:999px; box-shadow:0 4px 10px rgba(34,197,94,.3); white-space:nowrap; }

    .pricing-card__name { font-size:1.3rem; font-weight:800; color:var(--app-text); margin:0; letter-spacing:-.01em; }
    .pricing-card__desc { font-size:.85rem; color:var(--app-text-muted); margin:.3rem 0 0; min-height:1.2em; }

    .pricing-card__price { margin:1.1rem 0 .2rem; display:flex; align-items:baseline; gap:.4rem; flex-wrap:wrap; }
    .pricing-card__amount { font-size:2.1rem; font-weight:800; color:var(--app-text); letter-spacing:-.02em; line-height:1; }
    .pricing-card__per { font-size:.85rem; color:var(--app-text-muted); font-weight:500; }
    .pricing-card__save { display:inline-flex; align-items:center; gap:.35rem; font-size:.78rem; font-weight:600; color:#16a34a; background:rgba(34,197,94,.1); padding:.25rem .6rem; border-radius:8px; margin-top:.2rem; align-self:flex-start; }
    .pricing-card__save-spacer { height:.2rem; }

    .pricing-card__divider { display:flex; align-items:center; gap:.75rem; margin:1.25rem 0 1rem; color:var(--app-text-muted); font-size:.72rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em; }
    .pricing-card__divider::before, .pricing-card__divider::after { content:""; flex:1; height:1px; background:var(--app-border); }

    .pricing-card__features { list-style:none; padding:0; margin:0 0 1.5rem; display:grid; gap:.6rem; }
    .pricing-card__features li { display:flex; align-items:center; gap:.65rem; font-size:.9rem; color:var(--app-text); }
    .pricing-card__features .tick { width:20px; height:20px; border-radius:50%; background:rgba(34,197,94,.14); color:#16a34a; display:grid; place-items:center; font-size:.75rem; flex-shrink:0; }

    .pricing-card__cta { margin-top:auto; }
    .pricing-btn { border:0; border-radius:11px; font-weight:700; padding:.7rem 1rem; font-size:.95rem; transition:transform .12s, box-shadow .18s, filter .18s; }
    .pricing-btn--primary { color:#fff; background:linear-gradient(135deg, var(--app-primary,#4f46e5), #6366f1); box-shadow:0 6px 16px rgba(79,70,229,.3); }
    .pricing-btn--primary:hover { filter:brightness(1.05); transform:translateY(-1px); box-shadow:0 10px 22px rgba(79,70,229,.38); }
    .pricing-btn--current { background:rgba(34,197,94,.12); color:#16a34a; }
    .pricing-btn--muted { background:var(--app-surface-2,#f1f5f9); color:var(--app-text-muted); }

    .pay-row { display:flex; gap:.5rem; margin-bottom:.65rem; }
    .pay-opt { flex:1; }
    .pay-opt input { position:absolute; opacity:0; width:0; height:0; }
    .pay-opt span { display:flex; align-items:center; justify-content:center; gap:.4rem; padding:.55rem; border:1.5px solid var(--app-border); border-radius:10px; font-size:.85rem; cursor:pointer; font-weight:600; color:var(--app-text-muted); transition:all .15s; }
    .pay-opt input:checked + span { border-color:var(--app-primary,#4f46e5); color:var(--app-primary,#4f46e5); background:rgba(99,102,241,.07); }
    .auto-renew { display:flex; align-items:flex-start; gap:.5rem; font-size:.78rem; color:var(--app-text-muted); cursor:pointer; margin-bottom:.85rem; }
    .auto-renew .form-check-input { margin-top:.1rem; flex-shrink:0; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    let cycle = 'monthly';
    const toggle = document.querySelector('.cycle-toggle');
    const btns = document.querySelectorAll('.cycle-btn');
    const apply = () => {
        toggle?.classList.toggle('is-yearly', cycle === 'yearly');
        btns.forEach(b => b.classList.toggle('is-active', b.dataset.cycle === cycle));
        document.querySelectorAll('.pricing-card__price').forEach(p => {
            p.querySelector('.pricing-card__amount').textContent = p.dataset[cycle];
            const per = p.querySelector('.pricing-card__per');
            per.textContent = per.dataset[cycle];
        });
        document.querySelectorAll('.pricing-card__save[data-save]').forEach(s => { s.hidden = cycle !== 'yearly'; });
        document.querySelectorAll('.cycle-input').forEach(i => { i.value = cycle; });
    };
    btns.forEach(b => b.addEventListener('click', () => { cycle = b.dataset.cycle; apply(); }));
    apply();
})();
</script>
@endpush
