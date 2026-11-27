<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Billing\Plan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Platform-level PayPal client (no SDK) for charging tenants' subscriptions.
 * Uses the master admin's PayPal credentials from PlatformSettings.
 *
 *  - One-time (non-recurring): Orders v2 → approve link → capture.
 *  - Recurring: Subscriptions API — a billing product + plan are created from
 *    the Plan, then a subscription is started and the user approves it.
 */
class PlatformPayPal
{
    public const SUPPORTED = ['AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'THB', 'USD'];
    protected const ZERO_DECIMAL = ['HUF', 'JPY', 'TWD'];

    public static function baseUrl(): string
    {
        return PlatformSettings::paypalMode() === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public static function currency(): string
    {
        $cur = PlatformSettings::currency();

        return in_array($cur, self::SUPPORTED, true) ? $cur : 'USD';
    }

    public static function amountString(float $value, ?string $currency = null): string
    {
        $cur = $currency ?: self::currency();

        return in_array($cur, self::ZERO_DECIMAL, true)
            ? number_format(round($value), 0, '.', '')
            : number_format($value, 2, '.', '');
    }

    public static function intervalFor(string $cycle): string
    {
        return $cycle === 'yearly' ? 'YEAR' : 'MONTH';
    }

    // ── REST ─────────────────────────────────────────────────────────────

    /** @throws RuntimeException */
    public static function token(bool $fresh = false): string
    {
        $id = PlatformSettings::paypalClientId();
        $sec = PlatformSettings::paypalSecret();
        if ($id === '' || $sec === '') {
            throw new RuntimeException('PayPal billing is not configured.');
        }
        $key = 'platform:paypal:token:'.md5($id.PlatformSettings::paypalMode());
        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addMinutes(50), function () use ($id, $sec) {
            try {
                $r = Http::withBasicAuth($id, $sec)->asForm()->timeout(20)
                    ->post(self::baseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);
            } catch (Throwable $e) {
                report($e);
                throw new RuntimeException('Could not reach PayPal. Please try again.');
            }
            if (! $r->ok() || ! $r->json('access_token')) {
                $msg = $r->json('error_description') ?? $r->json('error') ?? ('HTTP '.$r->status());
                throw new RuntimeException('PayPal rejected the credentials: '.$msg);
            }

            return (string) $r->json('access_token');
        });
    }

    /** @throws RuntimeException */
    public static function api(string $method, string $path, array $body = [], array $headers = []): array
    {
        $attempt = function (bool $fresh) use ($method, $path, $body, $headers) {
            $req = Http::withToken(self::token($fresh))->acceptJson()->timeout(25)->withHeaders($headers);

            return strtoupper($method) === 'GET'
                ? $req->get(self::baseUrl().$path)
                : $req->post(self::baseUrl().$path, $body ?: null);
        };
        try {
            $r = $attempt(false);
            if ($r->status() === 401) {
                $r = $attempt(true);
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            throw new RuntimeException('Could not reach PayPal. Please try again.');
        }
        $json = $r->json() ?? [];
        if ($r->failed()) {
            $msg = $json['details'][0]['description'] ?? $json['message'] ?? $json['error_description'] ?? ('PayPal error '.$r->status());
            report(new RuntimeException("PayPal $method $path: $msg"));
            throw new RuntimeException($msg);
        }

        return $json;
    }

    protected static function approveLink(array $res): string
    {
        foreach (($res['links'] ?? []) as $l) {
            if (($l['rel'] ?? '') === 'approve' || ($l['rel'] ?? '') === 'payer-action') {
                return (string) $l['href'];
            }
        }

        return '';
    }

    // ── One-time (Orders v2) ─────────────────────────────────────────────

    /** Create a one-time order; returns ['id' => order id, 'approve' => url]. */
    public static function createOrder(float $value, string $tenantId, string $reference, string $brand, string $returnUrl, string $cancelUrl): array
    {
        $currency = self::currency();
        $amount = self::amountString($value, $currency);
        $res = self::api('POST', '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $reference,
                'custom_id' => $tenantId,
                'description' => $brand.' subscription',
                'amount' => ['currency_code' => $currency, 'value' => $amount],
            ]],
            'payment_source' => ['paypal' => [
                'experience_context' => [
                    'brand_name' => $brand,
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                ],
            ]],
        ], ['PayPal-Request-Id' => 'sub-order-'.$reference]);

        return ['id' => (string) ($res['id'] ?? ''), 'approve' => self::approveLink($res)];
    }

    public static function captureOrder(string $orderId): array
    {
        return self::api('POST', '/v2/checkout/orders/'.$orderId.'/capture', [], ['PayPal-Request-Id' => 'sub-capture-'.$orderId]);
    }

    public static function orderCaptureId(array $po): ?string
    {
        return $po['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;
    }

    public static function orderIsCompleted(array $po): bool
    {
        $status = $po['purchase_units'][0]['payments']['captures'][0]['status'] ?? ($po['status'] ?? '');

        return in_array($status, ['COMPLETED', 'APPROVED'], true);
    }

    // ── Recurring (Subscriptions API) ────────────────────────────────────

    /**
     * Ensure the plan has a PayPal product and billing plan for both cycles.
     * Returns the ids to persist (only created ones).
     *
     * @return array<string,string>
     */
    public static function syncPlan(Plan $plan): array
    {
        $out = [];
        $currency = self::currency();

        $productId = $plan->paypal_product_id;
        if (! $productId) {
            $p = self::api('POST', '/v1/catalogs/products', [
                'name' => $plan->name,
                'type' => 'SERVICE',
                'category' => 'SOFTWARE',
            ], ['PayPal-Request-Id' => 'prod-'.$plan->id]);
            $productId = (string) ($p['id'] ?? '');
            $out['paypal_product_id'] = $productId;
        }

        foreach (['monthly' => 'paypal_plan_monthly_id', 'yearly' => 'paypal_plan_yearly_id'] as $cycle => $col) {
            $price = $plan->priceFor($cycle);
            if ($productId && ! $plan->{$col} && $price > 0) {
                $bp = self::api('POST', '/v1/billing/plans', [
                    'product_id' => $productId,
                    'name' => $plan->name.' ('.$cycle.')',
                    'status' => 'ACTIVE',
                    'billing_cycles' => [[
                        'frequency' => ['interval_unit' => self::intervalFor($cycle), 'interval_count' => 1],
                        'tenure_type' => 'REGULAR',
                        'sequence' => 1,
                        'total_cycles' => 0,
                        'pricing_scheme' => ['fixed_price' => ['value' => self::amountString($price, $currency), 'currency_code' => $currency]],
                    ]],
                    'payment_preferences' => [
                        'auto_bill_outstanding' => true,
                        'setup_fee_failure_action' => 'CONTINUE',
                        'payment_failure_threshold' => 1,
                    ],
                ], ['PayPal-Request-Id' => 'bp-'.$plan->id.'-'.$cycle]);
                $out[$col] = (string) ($bp['id'] ?? '');
            }
        }

        return $out;
    }

    public static function planIdFor(Plan $plan, string $cycle): ?string
    {
        return $cycle === 'yearly' ? $plan->paypal_plan_yearly_id : $plan->paypal_plan_monthly_id;
    }

    /** Start a recurring subscription; returns ['id' => sub id, 'approve' => url]. */
    public static function createSubscription(Plan $plan, string $cycle, string $tenantId, string $brand, string $returnUrl, string $cancelUrl): array
    {
        $cycle = $cycle === 'yearly' ? 'yearly' : 'monthly';
        $planId = self::planIdFor($plan, $cycle);
        if (! $planId) {
            self::syncPlan($plan);
            $plan->refresh();
            $planId = self::planIdFor($plan, $cycle);
        }
        if (! $planId) {
            throw new RuntimeException('This plan is not yet set up for recurring PayPal billing.');
        }

        $res = self::api('POST', '/v1/billing/subscriptions', [
            'plan_id' => $planId,
            'custom_id' => $tenantId,
            'application_context' => [
                'brand_name' => $brand,
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'SUBSCRIBE_NOW',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ], ['PayPal-Request-Id' => 'sub-'.$tenantId.'-'.$plan->id.'-'.$cycle]);

        return ['id' => (string) ($res['id'] ?? ''), 'approve' => self::approveLink($res)];
    }

    public static function getSubscription(string $subscriptionId): array
    {
        return self::api('GET', '/v1/billing/subscriptions/'.$subscriptionId);
    }

    public static function subscriptionIsActive(array $sub): bool
    {
        return ($sub['status'] ?? '') === 'ACTIVE';
    }

    public static function cancelSubscription(string $subscriptionId, string $reason = 'Cancelled by user'): void
    {
        try {
            self::api('POST', '/v1/billing/subscriptions/'.$subscriptionId.'/cancel', ['reason' => $reason]);
        } catch (Throwable) {
        }
    }

    // ── Webhook verification ─────────────────────────────────────────────

    /**
     * Verify a PayPal webhook using the configured webhook id. Returns false if
     * no webhook id is set or verification cannot be completed — the caller then
     * decides whether to require it.
     *
     * @param array<string,array<int,string>> $headers Symfony headers->all() (lowercase keys)
     */
    public static function verifyWebhook(array $headers, string $body): bool
    {
        $webhookId = trim((string) PlatformSettings::get('paypal_webhook_id', ''));
        if ($webhookId === '') {
            return false;
        }

        $h = fn (string $k) => $headers[$k][0] ?? ($headers[strtolower($k)][0] ?? null);

        $transmissionId = $h('paypal-transmission-id');
        $transmissionTime = $h('paypal-transmission-time');
        $certUrl = $h('paypal-cert-url');
        $authAlgo = $h('paypal-auth-algo');
        $transmissionSig = $h('paypal-transmission-sig');

        if (! $transmissionId || ! $transmissionSig || ! $certUrl) {
            return false;
        }

        try {
            $res = self::api('POST', '/v1/notifications/verify-webhook-signature', [
                'transmission_id' => $transmissionId,
                'transmission_time' => $transmissionTime,
                'cert_url' => $certUrl,
                'auth_algo' => $authAlgo,
                'transmission_sig' => $transmissionSig,
                'webhook_id' => $webhookId,
                'webhook_event' => json_decode($body, true) ?: new \stdClass(),
            ]);
        } catch (Throwable) {
            return false;
        }

        return ($res['verification_status'] ?? '') === 'SUCCESS';
    }
}
