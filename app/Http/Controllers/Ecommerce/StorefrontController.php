<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/** Storefront & payment settings. */
class StorefrontController extends EcommerceController
{
    public function index()
    {
        $this->need('ecommerce.settings');

        return view('ecommerce.storefront.index', [
            'store' => [
                'store_enabled' => Settings::get('store_enabled', '1'),
                'store_name' => Settings::get('store_name', Settings::get('company_name', 'My Store')),
                'store_tagline' => Settings::get('store_tagline', ''),
                'store_free_shipping_over' => Settings::get('store_free_shipping_over', ''),
                // Payment methods
                'store_cod' => Settings::get('store_cod', '1'),
                'store_bank_transfer' => Settings::get('store_bank_transfer', '0'),
                'store_bank_details' => Settings::get('store_bank_details', ''),
                // Stripe
                'pay_stripe_enabled' => Settings::get('pay_stripe_enabled', '0'),
                'stripe_publishable_key' => Settings::get('stripe_publishable_key', ''),
                'stripe_secret_set' => (bool) Settings::get('stripe_secret_key'),
                'stripe_webhook_set' => (bool) Settings::get('stripe_webhook_secret'),
                // PayPal
                'pay_paypal_enabled' => Settings::get('pay_paypal_enabled', '0'),
                'paypal_client_id' => Settings::get('paypal_client_id', ''),
                'paypal_secret_set' => (bool) Settings::get('paypal_secret'),
                'paypal_mode' => Settings::get('paypal_mode', 'sandbox'),
            ],
            'tenantId' => auth()->user()->tenant_id,
        ]);
    }

    public function update(Request $request)
    {
        $this->need('ecommerce.settings');

        $data = $request->validate([
            'store_name' => ['required', 'string', 'max:120'],
            'store_tagline' => ['nullable', 'string', 'max:200'],
            'store_free_shipping_over' => ['nullable', 'numeric', 'min:0'],
            'store_bank_details' => ['nullable', 'string', 'max:1000'],
            'stripe_publishable_key' => ['nullable', 'string', 'max:255'],
            'stripe_secret_key' => ['nullable', 'string', 'max:255'],
            'stripe_webhook_secret' => ['nullable', 'string', 'max:255'],
            'paypal_client_id' => ['nullable', 'string', 'max:255'],
            'paypal_secret' => ['nullable', 'string', 'max:255'],
            'paypal_mode' => ['nullable', 'in:sandbox,live'],
        ]);

        $set = [
            'store_name' => $data['store_name'],
            'store_tagline' => $data['store_tagline'] ?? '',
            'store_free_shipping_over' => $data['store_free_shipping_over'] ?? '',
            'store_bank_details' => $data['store_bank_details'] ?? '',
            'store_enabled' => $request->boolean('store_enabled') ? '1' : '0',
            'store_cod' => $request->boolean('store_cod') ? '1' : '0',
            'store_bank_transfer' => $request->boolean('store_bank_transfer') ? '1' : '0',
            'pay_stripe_enabled' => $request->boolean('pay_stripe_enabled') ? '1' : '0',
            'stripe_publishable_key' => trim((string) ($data['stripe_publishable_key'] ?? '')),
            'pay_paypal_enabled' => $request->boolean('pay_paypal_enabled') ? '1' : '0',
            'paypal_client_id' => trim((string) ($data['paypal_client_id'] ?? '')),
            'paypal_mode' => $data['paypal_mode'] ?? 'sandbox',
        ];

        // Secrets: encrypt, and only overwrite when a new value was typed
        // (the form submits these blank unless the admin changes them).
        foreach (['stripe_secret_key', 'stripe_webhook_secret', 'paypal_secret'] as $secret) {
            $val = trim((string) ($data[$secret] ?? ''));
            if ($val !== '') {
                $set[$secret] = Crypt::encryptString($val);
            }
        }

        Settings::set($set);

        return back()->with('success', 'Store & payment settings saved.');
    }
}
