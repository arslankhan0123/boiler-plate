<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Billing\Plan;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Platform-level Stripe client (no SDK) used to charge tenants for their
 * subscriptions. Uses the master admin's Stripe credentials from
 * PlatformSettings. Supports both one-time checkout (mode=payment, for
 * non-recurring subscriptions that simply expire) and recurring checkout
 * (mode=subscription, which needs a Stripe Price created from the plan).
 */
class PlatformPayments
{
    protected const ZERO_DECIMAL = ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'];
    protected const THREE_DECIMAL = ['bhd', 'jod', 'kwd', 'omr', 'tnd'];

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

    public static function intervalFor(string $cycle): string
    {
        return $cycle === 'yearly' ? 'year' : 'month';
    }

    // ── REST ─────────────────────────────────────────────────────────────

    /** @throws RuntimeException */
    public static function stripe(string $method, string $path, array $params = []): array
    {
        $key = PlatformSettings::stripeSecretKey();
        if ($key === '') {
            throw new RuntimeException('Card billing is not configured.');
        }
        try {
            $req = Http::withToken($key)->timeout(25)->acceptJson()->asForm()
                ->withHeaders(['Stripe-Version' => '2024-06-20']);
            $res = strtoupper($method) === 'GET'
                ? $req->get('https://api.stripe.com'.$path, $params)
                : $req->post('https://api.stripe.com'.$path, $params);
        } catch (Throwable $e) {
            report($e);
            throw new RuntimeException('Could not reach Stripe. Please try again.');
        }
        $json = $res->json() ?? [];
        if (! $res->ok()) {
            $msg = $json['error']['message'] ?? ('Stripe error ('.$res->status().').');
            report(new RuntimeException('Stripe '.$method.' '.$path.': '.$msg));
            throw new RuntimeException($msg);
        }

        return $json;
    }

    // ── Dynamic product / price (recurring) ──────────────────────────────

    /**
     * Ensure the plan has a Stripe product and a recurring price for both cycles.
     * Returns the ids to persist on the plan (only the ones that changed/created).
     *
     * @return array<string,string>
     */
    public static function syncPlan(Plan $plan): array
    {
        $out = [];
        $currency = strtolower($plan->currency ?: PlatformSettings::currency());

        // Product
        $productId = $plan->stripe_product_id;
        if (! $productId) {
            $p = self::stripe('POST', '/v1/products', [
                'name' => $plan->name,
                'metadata[plan_id]' => (string) $plan->id,
            ]);
            $productId = (string) ($p['id'] ?? '');
            $out['stripe_product_id'] = $productId;
        }

        // Monthly recurring price
        if ($productId && ! $plan->stripe_price_monthly_id && (float) $plan->price_monthly > 0) {
            $price = self::stripe('POST', '/v1/prices', [
                'product' => $productId,
                'currency' => $currency,
                'unit_amount' => self::minorUnits((float) $plan->price_monthly, $currency),
                'recurring[interval]' => 'month',
                'nickname' => $plan->name.' (monthly)',
            ]);
            $out['stripe_price_monthly_id'] = (string) ($price['id'] ?? '');
        }

        // Yearly recurring price
        if ($productId && ! $plan->stripe_price_yearly_id && (float) $plan->price_yearly > 0) {
            $price = self::stripe('POST', '/v1/prices', [
                'product' => $productId,
                'currency' => $currency,
                'unit_amount' => self::minorUnits((float) $plan->price_yearly, $currency),
                'recurring[interval]' => 'year',
                'nickname' => $plan->name.' (yearly)',
            ]);
            $out['stripe_price_yearly_id'] = (string) ($price['id'] ?? '');
        }

        return $out;
    }

    public static function priceIdFor(Plan $plan, string $cycle): ?string
    {
        return $cycle === 'yearly' ? $plan->stripe_price_yearly_id : $plan->stripe_price_monthly_id;
    }

    // ── Checkout sessions ────────────────────────────────────────────────

    /**
     * Hosted Stripe Checkout. $recurring decides subscription vs one-time.
     * Returns ['id' => session id, 'url' => redirect url].
     *
     * @param array{tenant_id:string,plan:Plan,cycle:string,recurring:bool,success_url:string,cancel_url:string,email?:?string,subscription_id?:?int} $o
     */
    public static function createCheckoutSession(array $o): array
    {
        /** @var Plan $plan */
        $plan = $o['plan'];
        $cycle = $o['cycle'] === 'yearly' ? 'yearly' : 'monthly';
        $currency = strtolower($plan->currency ?: PlatformSettings::currency());
        $amount = $plan->priceFor($cycle);

        $params = [
            'success_url' => $o['success_url'],
            'cancel_url' => $o['cancel_url'],
            'client_reference_id' => (string) ($o['subscription_id'] ?? ''),
            'metadata[tenant_id]' => $o['tenant_id'],
            'metadata[plan_id]' => (string) $plan->id,
            'metadata[cycle]' => $cycle,
            'metadata[subscription_id]' => (string) ($o['subscription_id'] ?? ''),
        ];
        if (! empty($o['email'])) {
            $params['customer_email'] = $o['email'];
        }

        if (! empty($o['recurring'])) {
            $priceId = self::priceIdFor($plan, $cycle);
            if (! $priceId) {
                // Lazily sync the plan to Stripe if the price is missing.
                self::syncPlan($plan);
                $plan->refresh();
                $priceId = self::priceIdFor($plan, $cycle);
            }
            if (! $priceId) {
                throw new RuntimeException('This plan is not yet set up for recurring billing.');
            }
            $params['mode'] = 'subscription';
            $params['line_items[0][price]'] = $priceId;
            $params['line_items[0][quantity]'] = 1;
            $params['subscription_data[metadata][tenant_id]'] = $o['tenant_id'];
            $params['subscription_data[metadata][plan_id]'] = (string) $plan->id;
        } else {
            $params['mode'] = 'payment';
            $params['line_items[0][price_data][currency]'] = $currency;
            $params['line_items[0][price_data][unit_amount]'] = self::minorUnits($amount, $currency);
            $params['line_items[0][price_data][product_data][name]'] = $plan->name.' ('.$cycle.')';
            $params['line_items[0][quantity]'] = 1;
            $params['payment_intent_data[metadata][tenant_id]'] = $o['tenant_id'];
            $params['payment_intent_data[metadata][plan_id]'] = (string) $plan->id;
        }

        $session = self::stripe('POST', '/v1/checkout/sessions', $params);

        return [
            'id' => (string) ($session['id'] ?? ''),
            'url' => (string) ($session['url'] ?? ''),
        ];
    }

    public static function retrieveSession(string $id): array
    {
        return self::stripe('GET', '/v1/checkout/sessions/'.$id);
    }

    public static function cancelSubscription(string $subscriptionId): void
    {
        try {
            self::stripe('POST', '/v1/subscriptions/'.$subscriptionId.'/cancel');
        } catch (Throwable) {
        }
    }

    // ── Webhook ──────────────────────────────────────────────────────────

    /** Verify a Stripe-Signature header against the platform webhook secret. */
    public static function verifyWebhook(string $payload, ?string $sigHeader, int $tolerance = 300): bool
    {
        $secret = PlatformSettings::stripeWebhookSecret();
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
}
