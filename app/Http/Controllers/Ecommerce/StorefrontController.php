<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Models\Domain;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/** Storefront, payment and custom-domain settings. */
class StorefrontController extends EcommerceController
{
    public function index()
    {
        $this->need('ecommerce.settings');
        $tid = auth()->user()->tenant_id;

        return view('ecommerce.storefront.index', [
            'store' => [
                'store_enabled' => Settings::get('store_enabled', '1'),
                'store_name' => Settings::get('store_name', Settings::get('company_name', 'My Store')),
                'store_tagline' => Settings::get('store_tagline', ''),
                'store_free_shipping_over' => Settings::get('store_free_shipping_over', ''),
                'store_cod' => Settings::get('store_cod', '1'),
                'store_bank_transfer' => Settings::get('store_bank_transfer', '0'),
                'store_bank_details' => Settings::get('store_bank_details', ''),
                'pay_stripe_enabled' => Settings::get('pay_stripe_enabled', '0'),
                'stripe_publishable_key' => Settings::get('stripe_publishable_key', ''),
                'stripe_secret_set' => (bool) Settings::get('stripe_secret_key'),
                'stripe_webhook_set' => (bool) Settings::get('stripe_webhook_secret'),
                'pay_paypal_enabled' => Settings::get('pay_paypal_enabled', '0'),
                'paypal_client_id' => Settings::get('paypal_client_id', ''),
                'paypal_secret_set' => (bool) Settings::get('paypal_secret'),
                'paypal_mode' => Settings::get('paypal_mode', 'sandbox'),
            ],
            'tenantId' => $tid,
            'domains' => Domain::query()->where('tenant_id', $tid)->orderBy('created_at')->get(),
            'platformHost' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: request()->getHost(),
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

        foreach (['stripe_secret_key', 'stripe_webhook_secret', 'paypal_secret'] as $secret) {
            $val = trim((string) ($data[$secret] ?? ''));
            if ($val !== '') {
                $set[$secret] = Crypt::encryptString($val);
            }
        }

        Settings::set($set);

        return back()->with('success', 'Store & payment settings saved.');
    }

    // ───────────────────────── Custom domains ─────────────────────────

    public function addDomain(Request $request)
    {
        $this->need('ecommerce.settings');
        $raw = strtolower(trim((string) $request->input('domain')));
        // Strip scheme, path, port and a trailing dot.
        $raw = preg_replace('#^https?://#', '', $raw);
        $raw = explode('/', $raw)[0];
        $raw = explode(':', $raw)[0];
        $raw = rtrim($raw, '.');

        $request->merge(['domain' => $raw])->validate([
            'domain' => ['required', 'string', 'max:253', 'regex:/^(?=.{1,253}$)([a-z0-9](-?[a-z0-9])*\.)+[a-z]{2,}$/'],
        ], [], ['domain' => 'domain']);

        // Not a platform/central host, and not already taken.
        $central = array_map('strtolower', (array) config('tenancy.central_domains', []));
        $central[] = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if (in_array($raw, $central, true)) {
            return back()->with('error', 'That domain is reserved by the platform.');
        }
        if (Domain::query()->where('domain', $raw)->exists()) {
            return back()->with('error', 'That domain is already connected to a store.');
        }

        Domain::query()->create([
            'domain' => $raw,
            'tenant_id' => auth()->user()->tenant_id,
            'token' => Str::random(40),
            'verified_at' => null,
        ]);

        return back()->with('success', 'Domain added. Add the DNS records below, then click Verify.');
    }

    public function verifyDomain(Request $request, Domain $domain)
    {
        $this->need('ecommerce.settings');
        abort_unless((string) $domain->tenant_id === (string) auth()->user()->tenant_id, 403);

        $records = @dns_get_record('_store-verify.'.$domain->domain, DNS_TXT) ?: [];
        $ok = collect($records)->contains(fn ($r) => trim((string) ($r['txt'] ?? '')) === $domain->token);

        if (! $ok) {
            return back()->with('error', 'We could not find the verification TXT record yet. DNS changes can take a few minutes to propagate — please try again shortly.');
        }

        $domain->forceFill(['verified_at' => now()])->save();

        return back()->with('success', 'Domain verified! Your store is now served on '.$domain->domain.' (once DNS + SSL point to the platform).');
    }

    public function removeDomain(Request $request, Domain $domain)
    {
        $this->need('ecommerce.settings');
        abort_unless((string) $domain->tenant_id === (string) auth()->user()->tenant_id, 403);
        $domain->delete();

        return back()->with('success', 'Domain removed.');
    }
}
