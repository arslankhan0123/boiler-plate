@extends('layouts.admin.main')
@section('title', 'Billing settings')
@section('subtitle', 'Your Stripe & PayPal for charging tenants')

@section('content')
<div class="app-content">
    <x-admin.page-header title="Billing settings" subtitle="The payment accounts used to charge tenants for their subscriptions.">
        <x-admin.button variant="light" :href="route('subscriptions.index')" icon="receipt">Subscriptions</x-admin.button>
    </x-admin.page-header>

    <x-admin.alerts />

    <form method="POST" action="{{ route('platformbilling.update') }}" class="app-form-grid">
        @csrf
        @method('PUT')

        @php $currencyOptions = collect(\App\Support\Billing\PlatformSettings::SUPPORTED_CURRENCIES)->mapWithKeys(fn ($c) => [$c => $c])->all(); @endphp
        <div class="app-card app-form-card">
            <x-admin.select name="platform_currency" label="Billing currency" :options="$currencyOptions"
                :value="old('platform_currency', $s['platform_currency'] ?? 'USD')" required
                hint="The currency tenant subscriptions are charged in." />

            {{-- ── Payment gateways (accordion) ── --}}
            <div class="pm">
                <div class="pm__head">
                    <span class="pm__icon"><i class="bi bi-credit-card-2-front"></i></span>
                    <div class="flex-grow-1">
                        <h2 class="pm__title">Payment gateways</h2>
                        <p class="pm__sub">Enable a gateway and add its keys. Only enabled gateways can charge tenants.</p>
                    </div>
                    <div class="pm__chips">
                        <span class="pm-chip {{ $stripeReady ? 'is-on' : '' }}"><span class="dot"></span>Stripe {{ $stripeReady ? 'ready' : 'off' }}</span>
                        <span class="pm-chip {{ $paypalReady ? 'is-on' : '' }}"><span class="dot"></span>PayPal {{ $paypalReady ? 'ready' : 'off' }}</span>
                    </div>
                </div>

                {{-- Stripe --}}
                @php $stripeOn = old('pay_stripe_enabled', $s['pay_stripe_enabled'] ?? '0') === '1'; @endphp
                <input type="hidden" name="pay_stripe_enabled" value="0">
                <div class="pm-method {{ $stripeOn ? 'is-on' : '' }}" data-pm>
                    <label class="pm-method__head">
                        <input type="checkbox" name="pay_stripe_enabled" value="1" class="pm-check" data-pm-toggle @checked($stripeOn)>
                        <span class="pm-method__body">
                            <span class="pm-method__title">Credit / Debit Card <span class="pm-via">via Stripe</span></span>
                            <span class="pm-method__desc">Visa, Mastercard, Amex &amp; more. Supports recurring subscriptions.</span>
                        </span>
                        <span class="pm-logo pm-logo--stripe">stripe</span>
                    </label>
                    <div class="pm-method__panel">
                        <x-admin.input name="stripe_publishable_key" label="Publishable key" :value="old('stripe_publishable_key', $s['stripe_publishable_key'] ?? '')" placeholder="pk_live_… or pk_test_…" />
                        <div class="row g-4">
                            <div class="col-md-6">
                                <x-admin.input name="stripe_secret_key" type="password" label="Secret key" autocomplete="off"
                                    :placeholder="$stripeSecretSet ? '•••••••• (leave blank to keep)' : 'sk_live_… or sk_test_…'" />
                            </div>
                            <div class="col-md-6">
                                <x-admin.input name="stripe_webhook_secret" type="password" label="Webhook signing secret" autocomplete="off"
                                    :placeholder="$stripeWebhookSet ? '•••••••• (leave blank to keep)' : 'whsec_…'" />
                            </div>
                        </div>
                        <div class="form-text">Webhook URL (add it in your Stripe dashboard): <code>{{ $webhookStripeUrl }}</code></div>
                    </div>
                </div>

                {{-- PayPal --}}
                @php $paypalOn = old('pay_paypal_enabled', $s['pay_paypal_enabled'] ?? '0') === '1'; @endphp
                <input type="hidden" name="pay_paypal_enabled" value="0">
                <div class="pm-method {{ $paypalOn ? 'is-on' : '' }}" data-pm>
                    <label class="pm-method__head">
                        <input type="checkbox" name="pay_paypal_enabled" value="1" class="pm-check" data-pm-toggle @checked($paypalOn)>
                        <span class="pm-method__body">
                            <span class="pm-method__title">PayPal</span>
                            <span class="pm-method__desc">PayPal balance, linked bank or guest card. Supports recurring subscriptions.</span>
                        </span>
                        <span class="pm-logo pm-logo--paypal"><i class="bi bi-paypal"></i>PayPal</span>
                    </label>
                    <div class="pm-method__panel">
                        <x-admin.select name="paypal_mode" label="Mode" :options="['sandbox' => 'Sandbox (testing)', 'live' => 'Live']" :value="old('paypal_mode', $s['paypal_mode'] ?? 'sandbox')" required />
                        <x-admin.input name="paypal_client_id" label="Client ID" :value="old('paypal_client_id', $s['paypal_client_id'] ?? '')" placeholder="PayPal REST app client ID" />
                        <div class="row g-4">
                            <div class="col-md-6">
                                <x-admin.input name="paypal_secret" type="password" label="Secret" autocomplete="off"
                                    :placeholder="$paypalSecretSet ? '•••••••• (leave blank to keep)' : 'PayPal REST app secret'" />
                            </div>
                            <div class="col-md-6">
                                <x-admin.input name="paypal_webhook_id" label="Webhook ID" :value="old('paypal_webhook_id', $s['paypal_webhook_id'] ?? '')" placeholder="For verifying PayPal webhooks" />
                            </div>
                        </div>
                        <div class="form-text">Webhook URL: <code>{{ $webhookPaypalUrl }}</code></div>
                    </div>
                </div>
            </div>

            <div class="app-form-actions">
                <x-admin.button class="btn-lg-app" icon="check2">Save settings</x-admin.button>
            </div>
        </div>

        <div class="app-aside">
            <x-admin.tips :items="[
                'These are YOUR platform accounts — tenants are charged here, separate from each tenant\'s own store Stripe/PayPal.',
                'Secret keys are encrypted at rest and never shown back to you.',
                'Recurring (card) subscriptions need the webhook URL added at the gateway so renewals and cancellations sync.',
                'Use Stripe/PayPal test keys first, then switch to live when you go to production.',
            ]" />
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .pm { margin-top: 1.25rem; }
    .pm__head { display:flex; align-items:center; gap:1rem; margin-bottom:1rem; }
    .pm__icon { width:48px; height:48px; border-radius:12px; display:grid; place-items:center; color:#fff; font-size:1.3rem;
        background:linear-gradient(135deg,#f59e0b,#f97316); flex-shrink:0; box-shadow:0 6px 16px rgba(249,115,22,.3); }
    .pm__title { font-size:1.1rem; font-weight:700; margin:0; color:var(--app-text); }
    .pm__sub { font-size:.85rem; color:var(--app-text-muted); margin:.1rem 0 0; }
    .pm__chips { display:flex; gap:.4rem; flex-wrap:wrap; }
    .pm-chip { display:inline-flex; align-items:center; gap:.4rem; font-size:.75rem; font-weight:600; padding:.3rem .7rem; border-radius:999px;
        background:var(--app-surface-2,#f1f5f9); color:var(--app-text-muted); }
    .pm-chip .dot { width:7px; height:7px; border-radius:50%; background:#cbd5e1; }
    .pm-chip.is-on { background:rgba(34,197,94,.12); color:#16a34a; }
    .pm-chip.is-on .dot { background:#22c55e; }

    .pm-method { border:1.5px solid var(--app-border); border-radius:14px; margin-bottom:.85rem; background:var(--app-surface); transition:border-color .18s, background .18s; overflow:hidden; }
    .pm-method.is-on { border-color:#818cf8; background:rgba(99,102,241,.03); }
    .pm-method__head { display:flex; align-items:center; gap:1rem; padding:1rem 1.15rem; margin:0; cursor:pointer; }
    .pm-method__head .pm-check { width:1.15rem; height:1.15rem; flex-shrink:0; accent-color:var(--app-primary,#4f46e5); cursor:pointer; margin:0; }
    .pm-method__body { flex-grow:1; min-width:0; }
    .pm-method__title { display:block; font-weight:600; color:var(--app-text); }
    .pm-method__title .pm-via { font-weight:500; color:var(--app-text-muted); font-size:.85rem; }
    .pm-method__desc { display:block; font-size:.82rem; color:var(--app-text-muted); margin-top:.1rem; }
    .pm-logo { flex-shrink:0; font-weight:800; font-size:1.05rem; letter-spacing:-.02em; }
    .pm-logo--stripe { color:#635bff; font-style:italic; }
    .pm-logo--paypal { color:#003087; display:inline-flex; align-items:center; gap:.25rem; font-size:1rem; }
    .pm-logo--paypal i { color:#0070ba; font-size:1.2rem; }

    .pm-method__panel { display:none; padding:0 1.15rem 1.15rem; border-top:1px dashed var(--app-border); padding-top:1rem; }
    .pm-method.is-on .pm-method__panel { display:block; animation:pmFade .2s ease; }
    @keyframes pmFade { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:none; } }
    @media (max-width:575px){ .pm__head{ flex-wrap:wrap; } .pm-logo{ display:none; } }
</style>
@endpush

@push('scripts')
<script>
(() => {
    document.querySelectorAll('.pm-method[data-pm]').forEach(card => {
        const box = card.querySelector('[data-pm-toggle]');
        if (!box) return;
        const sync = () => card.classList.toggle('is-on', box.checked);
        box.addEventListener('change', sync);
        sync();
    });
})();
</script>
@endpush
