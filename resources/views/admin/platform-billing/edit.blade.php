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

        <div class="app-card app-form-card">
            <x-admin.input name="platform_currency" label="Billing currency" :value="old('platform_currency', $s['platform_currency'] ?? 'USD')" maxlength="3" required
                hint="The currency tenant subscriptions are charged in (3-letter code)." />

            {{-- Stripe --}}
            <div class="form-section-title mt-4 d-flex align-items-center gap-2">
                <i class="bi bi-credit-card"></i> Stripe
                @if ($stripeReady)<x-admin.badge tone="success">Ready</x-admin.badge>@else<x-admin.badge tone="muted">Not set up</x-admin.badge>@endif
            </div>
            <label class="app-check mb-3">
                <input type="hidden" name="pay_stripe_enabled" value="0">
                <input type="checkbox" name="pay_stripe_enabled" value="1" class="form-check-input" @checked(old('pay_stripe_enabled', $s['pay_stripe_enabled'] ?? '0') === '1')>
                <span>Enable Stripe card payments for subscriptions</span>
            </label>
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
            <div class="form-text">Stripe webhook URL (add it in your Stripe dashboard): <code>{{ $webhookStripeUrl }}</code></div>

            {{-- PayPal --}}
            <div class="form-section-title mt-4 d-flex align-items-center gap-2">
                <i class="bi bi-paypal"></i> PayPal
                @if ($paypalReady)<x-admin.badge tone="success">Ready</x-admin.badge>@else<x-admin.badge tone="muted">Not set up</x-admin.badge>@endif
            </div>
            <label class="app-check mb-3">
                <input type="hidden" name="pay_paypal_enabled" value="0">
                <input type="checkbox" name="pay_paypal_enabled" value="1" class="form-check-input" @checked(old('pay_paypal_enabled', $s['pay_paypal_enabled'] ?? '0') === '1')>
                <span>Enable PayPal for subscriptions</span>
            </label>
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
            <div class="form-text">PayPal webhook URL: <code>{{ $webhookPaypalUrl }}</code></div>

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
    .app-check { display: flex; align-items: flex-start; gap: .6rem; font-size: .9rem; color: var(--app-text); cursor: pointer; }
    .app-check .form-check-input { margin-top: .15rem; flex-shrink: 0; }
</style>
@endpush
