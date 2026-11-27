<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionPayment;
use App\Support\Billing\BillingManager;
use App\Support\Billing\PlatformPayments;
use App\Support\Billing\PlatformPayPal;
use App\Support\Billing\PlatformSettings;
use Illuminate\Http\Request;

/**
 * Server-to-server webhooks from the PLATFORM Stripe/PayPal accounts. These keep
 * subscriptions in sync when payments complete away from the browser and when
 * recurring renewals, cancellations or failures happen later. CSRF-exempt;
 * authenticated by the gateway signature instead (see bootstrap/app.php).
 *
 * Everything here is central-DB only (Subscription/Plan force the central
 * connection), so no tenant context is needed.
 */
class PlatformWebhookController extends Controller
{
    // ── Stripe ───────────────────────────────────────────────────────────

    public function stripe(Request $request)
    {
        $payload = $request->getContent();
        if (! PlatformPayments::verifyWebhook($payload, $request->header('Stripe-Signature'))) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true) ?: [];
        $type = $event['type'] ?? '';
        $obj = $event['data']['object'] ?? [];

        switch ($type) {
            case 'checkout.session.completed':
                $this->stripeCheckoutCompleted($obj);
                break;

            case 'invoice.paid':
            case 'invoice.payment_succeeded':
                $this->stripeInvoicePaid($obj);
                break;

            case 'customer.subscription.deleted':
                $this->stripeSubscriptionEnded($obj);
                break;
        }

        return response()->json(['received' => true]);
    }

    private function stripeCheckoutCompleted(array $session): void
    {
        $subId = $session['metadata']['subscription_id'] ?? $session['client_reference_id'] ?? null;
        $sub = $subId ? Subscription::query()->find((int) $subId) : null;
        if (! $sub || $sub->isActive()) {
            return;
        }
        $paid = ($session['payment_status'] ?? '') === 'paid' || ($session['status'] ?? '') === 'complete';
        if (! $paid) {
            return;
        }
        $sub->update([
            'gateway_customer_id' => $session['customer'] ?? $sub->gateway_customer_id,
            'gateway_subscription_id' => $session['subscription'] ?? $sub->gateway_subscription_id,
        ]);
        BillingManager::activatePaid($sub);
        $this->logPayment($sub, (float) $sub->amount, Subscription::GATEWAY_STRIPE, $session['payment_intent'] ?? ($session['id'] ?? null));
    }

    private function stripeInvoicePaid(array $invoice): void
    {
        $stripeSubId = $invoice['subscription'] ?? null;
        if (! $stripeSubId) {
            return;
        }
        $sub = Subscription::query()->where('gateway_subscription_id', $stripeSubId)->orderByDesc('id')->first();
        if (! $sub) {
            return;
        }
        $payId = $invoice['payment_intent'] ?? $invoice['id'] ?? null;
        if ($payId && SubscriptionPayment::query()->where('gateway_payment_id', $payId)->exists()) {
            return; // already recorded (e.g. the first invoice handled at checkout)
        }
        // The very first invoice is the initial payment — only renew on later ones.
        if (($invoice['billing_reason'] ?? '') === 'subscription_cycle') {
            BillingManager::renew($sub);
        }
        $amount = isset($invoice['amount_paid']) ? ((int) $invoice['amount_paid']) / 100 : (float) $sub->amount;
        $this->logPayment($sub, $amount, Subscription::GATEWAY_STRIPE, $payId);
    }

    private function stripeSubscriptionEnded(array $subscription): void
    {
        $stripeSubId = $subscription['id'] ?? null;
        if (! $stripeSubId) {
            return;
        }
        $sub = Subscription::query()->where('gateway_subscription_id', $stripeSubId)->orderByDesc('id')->first();
        if ($sub && in_array($sub->status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_PENDING], true)) {
            BillingManager::cancel($sub, revokeNow: true);
        }
    }

    // ── PayPal ───────────────────────────────────────────────────────────

    public function paypal(Request $request)
    {
        $payload = $request->getContent();
        $event = json_decode($payload, true) ?: [];

        // Verify when a webhook id is configured; otherwise fall back to
        // re-reading the subscription from PayPal before acting.
        $verified = PlatformPayPal::verifyWebhook($request->headers->all(), $payload);
        if (PlatformSettings::get('paypal_webhook_id') && ! $verified) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $type = $event['event_type'] ?? '';
        $resource = $event['resource'] ?? [];

        switch ($type) {
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
                $this->paypalSubscriptionActivated($resource);
                break;

            case 'PAYMENT.SALE.COMPLETED':
                $this->paypalSaleCompleted($resource);
                break;

            case 'BILLING.SUBSCRIPTION.CANCELLED':
            case 'BILLING.SUBSCRIPTION.EXPIRED':
            case 'BILLING.SUBSCRIPTION.SUSPENDED':
                $this->paypalSubscriptionEnded($resource);
                break;
        }

        return response()->json(['received' => true]);
    }

    private function paypalSubscriptionActivated(array $resource): void
    {
        $ppSubId = $resource['id'] ?? null;
        if (! $ppSubId) {
            return;
        }
        $sub = Subscription::query()->where('gateway_subscription_id', $ppSubId)->orderByDesc('id')->first();
        if ($sub && ! $sub->isActive()) {
            BillingManager::activatePaid($sub);
            $this->logPayment($sub, (float) $sub->amount, Subscription::GATEWAY_PAYPAL, $ppSubId);
        }
    }

    private function paypalSaleCompleted(array $resource): void
    {
        $ppSubId = $resource['billing_agreement_id'] ?? null;
        if (! $ppSubId) {
            return;
        }
        $sub = Subscription::query()->where('gateway_subscription_id', $ppSubId)->orderByDesc('id')->first();
        if (! $sub) {
            return;
        }
        $payId = $resource['id'] ?? null;
        if ($payId && SubscriptionPayment::query()->where('gateway_payment_id', $payId)->exists()) {
            return;
        }
        // Renew only if this isn't the very first payment (sub already active with a future period).
        if ($sub->isActive() && $sub->ends_at && $sub->ends_at->isFuture()) {
            // initial payment already covered — still log it
        } else {
            BillingManager::renew($sub);
        }
        $amount = isset($resource['amount']['total']) ? (float) $resource['amount']['total'] : (float) $sub->amount;
        $this->logPayment($sub, $amount, Subscription::GATEWAY_PAYPAL, $payId);
    }

    private function paypalSubscriptionEnded(array $resource): void
    {
        $ppSubId = $resource['id'] ?? null;
        if (! $ppSubId) {
            return;
        }
        $sub = Subscription::query()->where('gateway_subscription_id', $ppSubId)->orderByDesc('id')->first();
        if ($sub && in_array($sub->status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_PENDING], true)) {
            BillingManager::cancel($sub, revokeNow: true);
        }
    }

    // ── Shared ───────────────────────────────────────────────────────────

    private function logPayment(Subscription $sub, float $amount, string $gateway, ?string $payId): void
    {
        if ($payId && SubscriptionPayment::query()->where('gateway_payment_id', $payId)->exists()) {
            return;
        }
        BillingManager::recordPayment($sub, $amount, $gateway, $payId, $sub->starts_at, $sub->ends_at);
    }
}
