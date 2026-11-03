<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Billing\PlatformSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Platform-only: the master admin's own Stripe / PayPal credentials, used to
 * charge tenants for their subscriptions. Secrets are stored encrypted and are
 * only ever shown as "set / not set" — never echoed back.
 */
class PlatformBillingController extends Controller
{
    public function edit()
    {
        return view('admin.platform-billing.edit', [
            's' => PlatformSettings::all(),
            'stripeSecretSet' => PlatformSettings::stripeSecretKey() !== '',
            'stripeWebhookSet' => PlatformSettings::stripeWebhookSecret() !== '',
            'paypalSecretSet' => PlatformSettings::paypalSecret() !== '',
            'stripeReady' => PlatformSettings::stripeReady(),
            'paypalReady' => PlatformSettings::paypalReady(),
            'webhookStripeUrl' => url('/billing/webhook/stripe'),
            'webhookPaypalUrl' => url('/billing/webhook/paypal'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'platform_currency' => ['required', 'string', 'size:3', function ($attr, $value, $fail) {
                if (! PlatformSettings::isSupportedCurrency($value)) {
                    $fail('Unsupported currency. Use a valid 3-letter code supported by Stripe, e.g. USD, EUR, GBP, PKR, AED.');
                }
            }],
            'pay_stripe_enabled' => ['required', 'boolean'],
            'stripe_publishable_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret_key' => ['nullable', 'string', 'max:255'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],
            'pay_paypal_enabled' => ['required', 'boolean'],
            'paypal_mode' => ['required', Rule::in(['sandbox', 'live'])],
            'paypal_client_id' => ['nullable', 'string', 'max:255'],
            'paypal_secret' => ['nullable', 'string', 'max:255'],
            'paypal_webhook_id' => ['nullable', 'string', 'max:255'],
        ]);

        $save = [
            'platform_currency' => strtoupper($data['platform_currency']),
            'pay_stripe_enabled' => $data['pay_stripe_enabled'] ? '1' : '0',
            'stripe_publishable_key' => $data['stripe_publishable_key'] ?? '',
            'pay_paypal_enabled' => $data['pay_paypal_enabled'] ? '1' : '0',
            'paypal_mode' => $data['paypal_mode'],
            'paypal_client_id' => $data['paypal_client_id'] ?? '',
            'paypal_webhook_id' => $data['paypal_webhook_id'] ?? '',
        ];

        // Only overwrite secrets when a new value was actually entered.
        foreach (['stripe_secret_key', 'stripe_webhook_secret', 'paypal_secret'] as $secret) {
            if (filled($data[$secret] ?? null)) {
                $save[$secret] = $data[$secret];
            }
        }

        PlatformSettings::set($save);

        return redirect()->route('platformbilling.edit')->with('success', 'Billing settings saved.');
    }
}
