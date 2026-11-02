<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Order;
use App\Models\Tenant;
use App\Support\Ecommerce\Payments;
use App\Support\Settings;
use Illuminate\Http\Request;

/**
 * POST /store/{tenant}/webhook/stripe
 *
 * Safety net for card payments that finish after the customer leaves the page
 * (bank redirects, "processing" debits, closed tabs). CSRF-exempt; the Stripe
 * signature is verified against the tenant's webhook secret instead.
 */
class StripeWebhookController extends Controller
{
    public function handle(string $tenant, Request $request)
    {
        // Switch to the tenant DB so Settings + orders resolve correctly.
        $t = Tenant::query()->find($tenant);
        if (! $t) {
            return response()->json(['error' => 'unknown tenant'], 404);
        }
        tenancy()->end();
        tenancy()->initialize($t);

        $payload = $request->getContent();
        if (! Payments::verifyWebhook($payload, $request->header('Stripe-Signature'))) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $event = json_decode($payload, true) ?: [];
        $type = $event['type'] ?? '';
        $pi = $event['data']['object'] ?? [];
        $piId = $pi['id'] ?? null;

        if (! $piId || ! str_starts_with($type, 'payment_intent.')) {
            return response()->json(['received' => true]);
        }

        $order = Order::where('payment_intent_id', $piId)->first()
            ?: (isset($pi['metadata']['order_no']) ? Order::where('order_no', $pi['metadata']['order_no'])->first() : null);

        if (! $order) {
            return response()->json(['received' => true, 'order' => 'unknown']);
        }

        switch ($type) {
            case 'payment_intent.succeeded':
                Payments::markPaid($order, $piId);
                break;
            case 'payment_intent.canceled':
                if ($order->payment_status !== 'paid') {
                    Payments::cancelAndRelease($order);
                }
                break;
        }

        return response()->json(['received' => true]);
    }
}
