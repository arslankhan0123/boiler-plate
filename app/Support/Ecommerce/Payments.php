<?php

declare(strict_types=1);

namespace App\Support\Ecommerce;

use App\Models\Ecommerce\Order;
use App\Support\Settings;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/**
 * Checkout payment methods + a tiny Stripe REST client (no SDK).
 *
 * Stripe is called directly over HTTPS. The secret key and webhook secret are
 * stored encrypted in the tenant settings and only ever used server-side; the
 * browser receives the publishable key and a client_secret.
 */
class Payments
{
    public const COD = 'cod';
    public const BANK = 'bank';
    public const STRIPE = 'card';
    public const PAYPAL = 'paypal';

    /** Methods the tenant has switched on, in display order: code => [label, desc, icon, tag]. */
    public static function enabledMethods(): array
    {
        $m = [];
        if (Settings::get('store_cod', '1') === '1') {
            $m[self::COD] = ['label' => 'Cash on Delivery', 'desc' => 'Pay in cash when your order arrives.', 'icon' => 'fa-money-bill-wave', 'tag' => 'Popular'];
        }
        if (self::stripeReady()) {
            $m[self::STRIPE] = ['label' => 'Credit / Debit Card', 'desc' => 'Visa, Mastercard, Amex and more. Secured by Stripe.', 'icon' => 'fa-credit-card', 'tag' => 'Instant'];
        }
        if (PayPal::enabled()) {
            $m[self::PAYPAL] = ['label' => 'PayPal', 'desc' => 'Pay with your PayPal balance, bank or card.', 'icon' => 'fa-brands fa-paypal', 'tag' => null];
        }
        if (Settings::get('store_bank_transfer', '0') === '1') {
            $m[self::BANK] = ['label' => 'Bank Transfer', 'desc' => 'Transfer to our account and upload the receipt.', 'icon' => 'fa-building-columns', 'tag' => null];
        }
        if (! $m) {
            $m[self::COD] = ['label' => 'Cash on Delivery', 'desc' => 'Pay in cash when your order arrives.', 'icon' => 'fa-money-bill-wave', 'tag' => null];
        }

        return $m;
    }

    /** Human label stored on the order for a method code. */
    public static function label(string $code): string
    {
        return self::enabledMethods()[$code]['label'] ?? match ($code) {
            self::STRIPE => 'Credit / Debit Card', self::PAYPAL => 'PayPal',
            self::BANK => 'Bank Transfer', default => 'Cash on Delivery',
        };
    }

    public static function isOnline(?string $code): bool
    {
        return in_array($code, [self::STRIPE, self::PAYPAL], true);
    }

    // ── Stripe config ───────────────────────────────────────────────────

    public static function stripeReady(): bool
    {
        return Settings::get('pay_stripe_enabled', '0') === '1'
            && self::stripePublishableKey() !== ''
            && self::stripeSecretKey() !== '';
    }

    public static function stripePublishableKey(): string
    {
        return trim((string) Settings::get('stripe_publishable_key', ''));
    }

    protected static function stripeSecretKey(): string
    {
        return self::decrypt((string) Settings::get('stripe_secret_key', ''));
    }

    public static function stripeTestMode(): bool
    {
        return str_starts_with(self::stripePublishableKey(), 'pk_test_');
    }

    /** Decrypt a stored secret; falls back to the raw value if it was stored plain. */
    public static function decrypt(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        try {
            return trim(Crypt::decryptString($value));
        } catch (\Throwable) {
            return $value;
        }
    }

    // ── Amounts ─────────────────────────────────────────────────────────

    protected const ZERO_DECIMAL = ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'];
    protected const THREE_DECIMAL = ['bhd', 'jod', 'kwd', 'omr', 'tnd'];

    /** Amount in Stripe minor units for the store currency. */
    public static function minorUnits(float $amount, string $currency): int
    {
        $cur = strtolower($currency);
        if (in_array($cur, self::ZERO_DECIMAL, true)) {
            return (int) round($amount);
        }
        if (in_array($cur, self::THREE_DECIMAL, true)) {
            return (int) round($amount * 1000);
        }

        return (int) round($amount * 100);
    }

    // ── Stripe REST ─────────────────────────────────────────────────────

    /** @throws \RuntimeException with a customer-safe message on failure */
    public static function stripe(string $method, string $path, array $params = []): array
    {
        $key = self::stripeSecretKey();
        if ($key === '') {
            throw new \RuntimeException('Card payments are not configured.');
        }
        try {
            $req = Http::withToken($key)->timeout(20)->acceptJson()->asForm()
                ->withHeaders(['Stripe-Version' => '2024-06-20']);
            $res = strtoupper($method) === 'GET'
                ? $req->get('https://api.stripe.com'.$path, $params)
                : $req->post('https://api.stripe.com'.$path, $params);
        } catch (\Throwable $e) {
            report($e);
            throw new \RuntimeException('Could not reach the payment provider. Please try again.');
        }
        $json = $res->json() ?? [];
        if (! $res->ok()) {
            $msg = $json['error']['message'] ?? ('Payment provider error ('.$res->status().').');
            report(new \RuntimeException('Stripe '.$method.' '.$path.': '.$msg));
            if (($json['error']['type'] ?? '') === 'invalid_request_error' && str_contains((string) $msg, 'API key')) {
                $msg = 'Card payments are temporarily unavailable.';
            }
            throw new \RuntimeException($msg);
        }

        return $json;
    }

    public static function createIntent(int $amount, string $currency, array $metadata = [], ?string $receiptEmail = null): array
    {
        $params = [
            'amount' => $amount,
            'currency' => strtolower($currency),
            'automatic_payment_methods[enabled]' => 'true',
            'description' => Settings::get('store_name', 'Store').' order',
        ];
        foreach ($metadata as $k => $v) {
            $params["metadata[$k]"] = (string) $v;
        }
        if ($receiptEmail) {
            $params['receipt_email'] = $receiptEmail;
        }

        return self::stripe('POST', '/v1/payment_intents', $params);
    }

    public static function updateIntent(string $id, array $params): array
    {
        return self::stripe('POST', '/v1/payment_intents/'.$id, $params);
    }

    public static function retrieveIntent(string $id): array
    {
        return self::stripe('GET', '/v1/payment_intents/'.$id);
    }

    public static function cancelIntent(string $id): void
    {
        try {
            self::stripe('POST', '/v1/payment_intents/'.$id.'/cancel');
        } catch (\Throwable) {
        }
    }

    /** Verify a Stripe-Signature header (HMAC-SHA256 over "t.payload"). */
    public static function verifyWebhook(string $payload, ?string $sigHeader, int $tolerance = 300): bool
    {
        $secret = self::decrypt((string) Settings::get('stripe_webhook_secret', ''));
        if ($secret === '' || ! $sigHeader) {
            return false;
        }
        $t = null;
        $sigs = [];
        foreach (explode(',', $sigHeader) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($k === 't') {
                $t = $v;
            }
            if ($k === 'v1' && $v) {
                $sigs[] = $v;
            }
        }
        if (! $t || ! $sigs || abs(time() - (int) $t) > $tolerance) {
            return false;
        }
        $expected = hash_hmac('sha256', $t.'.'.$payload, $secret);
        foreach ($sigs as $s) {
            if (hash_equals($expected, $s)) {
                return true;
            }
        }

        return false;
    }

    // ── Order state ─────────────────────────────────────────────────────

    /** Mark an order paid (idempotent). Returns true when this call made the change. */
    public static function markPaid(Order $order, ?string $intentId = null): bool
    {
        if ($order->payment_status === 'paid') {
            return false;
        }
        $order->forceFill([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_intent_id' => $intentId ?: $order->payment_intent_id,
            'status' => $order->status === 'pending' ? 'confirmed' : $order->status,
        ])->save();

        return true;
    }

    /** Release an unpaid online order so the shopper can retry. */
    public static function cancelAndRelease(Order $order): void
    {
        if ($order->status === 'cancelled' || $order->payment_status === 'paid') {
            return;
        }
        $order->forceFill(['status' => 'cancelled'])->save();
        if ($order->payment_method === self::STRIPE && $order->payment_intent_id) {
            self::cancelIntent($order->payment_intent_id);
        }
    }
}
