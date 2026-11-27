<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Support\Billing\BillingManager;
use App\Support\Billing\PlatformPayments;
use App\Support\Billing\PlatformPayPal;
use App\Support\Billing\PlatformSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Tenant-facing billing. A tenant sees the public plans (never trials), picks a
 * plan + billing cycle, and checks out using the PLATFORM's Stripe/PayPal. If
 * they keep "auto-renew" on and pay by card, it's a recurring subscription;
 * otherwise it's a one-time payment that simply expires at period end.
 */
class BillingController extends Controller
{
    private function tenantId(): string
    {
        $user = auth()->user();
        abort_if(! $user || $user->is_platform_admin || $user->tenant_id === null, 403, 'Billing is only available to tenant accounts.');

        return (string) $user->tenant_id;
    }

    private function brand(): string
    {
        return (string) config('app.name', 'Platform');
    }

    public function index()
    {
        $tenantId = $this->tenantId();

        return view('billing.index', [
            'active' => BillingManager::activeFor($tenantId),
            'plans' => Plan::query()->public()->ordered()->get(),
            'history' => Subscription::query()->forTenant($tenantId)->with('plan')->orderByDesc('id')->limit(10)->get(),
            'stripeReady' => PlatformSettings::stripeReady(),
            'paypalReady' => PlatformSettings::paypalReady(),
            'anyReady' => PlatformSettings::anyGatewayReady(),
        ]);
    }

    public function subscribe(Request $request)
    {
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'plan_id' => ['required', Rule::exists((new Plan)->getConnectionName().'.plans', 'id')->where('is_active', 1)->where('is_public', 1)],
            'cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'gateway' => ['required', Rule::in(['stripe', 'paypal'])],
            'auto_renew' => ['nullable', 'boolean'],
        ]);

        $plan = Plan::query()->findOrFail($data['plan_id']);
        $gateway = $data['gateway'];
        $recurring = $request->boolean('auto_renew');

        if ($gateway === 'stripe' && ! PlatformSettings::stripeReady()) {
            return back()->with('error', 'Card payments are not available right now.');
        }
        if ($gateway === 'paypal' && ! PlatformSettings::paypalReady()) {
            return back()->with('error', 'PayPal is not available right now.');
        }

        // Free plan → activate immediately, no gateway.
        if ($plan->priceFor($data['cycle']) <= 0) {
            $sub = BillingManager::createPending($tenantId, $plan, $data['cycle'], false, Subscription::GATEWAY_MANUAL, auth()->id());
            BillingManager::activatePaid($sub);

            return redirect()->route('billing.index')->with('success', 'Your plan is now active.');
        }

        try {
            $sub = BillingManager::createPending($tenantId, $plan, $data['cycle'], $recurring, $gateway, auth()->id());

            if ($gateway === 'stripe') {
                $success = route('billing.stripe.return', ['sub' => $sub->id]).'&session_id={CHECKOUT_SESSION_ID}';
                $cancel = route('billing.cancelCheckout', ['sub' => $sub->id]);
                $session = PlatformPayments::createCheckoutSession([
                    'tenant_id' => $tenantId,
                    'plan' => $plan,
                    'cycle' => $data['cycle'],
                    'recurring' => $recurring,
                    'success_url' => $success,
                    'cancel_url' => $cancel,
                    'email' => auth()->user()->email,
                    'subscription_id' => $sub->id,
                ]);
                $sub->update(['gateway_reference' => $session['id']]);

                return redirect()->away($session['url']);
            }

            // PayPal
            $success = route('billing.paypal.return', ['sub' => $sub->id]);
            $cancel = route('billing.cancelCheckout', ['sub' => $sub->id]);
            if ($recurring) {
                $res = PlatformPayPal::createSubscription($plan, $data['cycle'], $tenantId, $this->brand(), $success, $cancel);
                $sub->update(['gateway_subscription_id' => $res['id']]);
            } else {
                $res = PlatformPayPal::createOrder($plan->priceFor($data['cycle']), $tenantId, 'sub-'.$sub->id, $this->brand(), $success, $cancel);
                $sub->update(['gateway_reference' => $res['id']]);
            }
            if (empty($res['approve'])) {
                throw new \RuntimeException('PayPal did not return an approval link.');
            }

            return redirect()->away($res['approve']);
        } catch (Throwable $e) {
            report($e);
            if (isset($sub)) {
                $sub->update(['status' => Subscription::STATUS_CANCELLED, 'cancelled_at' => now()]);
            }

            return back()->with('error', 'Could not start checkout: '.$e->getMessage());
        }
    }

    public function stripeReturn(Request $request)
    {
        $tenantId = $this->tenantId();
        $sub = Subscription::query()->forTenant($tenantId)->findOrFail($request->integer('sub'));

        if ($sub->isActive()) {
            return redirect()->route('billing.index')->with('success', 'Your subscription is active.');
        }

        try {
            $sessionId = $request->query('session_id');
            if (! $sessionId) {
                return redirect()->route('billing.index')->with('error', 'Payment could not be confirmed.');
            }
            $session = PlatformPayments::retrieveSession($sessionId);
            $paid = ($session['payment_status'] ?? '') === 'paid' || ($session['status'] ?? '') === 'complete';

            if (! $paid) {
                return redirect()->route('billing.index')->with('error', 'Payment was not completed.');
            }

            $sub->update([
                'gateway_customer_id' => $session['customer'] ?? null,
                'gateway_subscription_id' => $session['subscription'] ?? $sub->gateway_subscription_id,
            ]);
            BillingManager::activatePaid($sub);
            BillingManager::recordPayment($sub, (float) $sub->amount, Subscription::GATEWAY_STRIPE, $session['payment_intent'] ?? $sessionId, $sub->starts_at, $sub->ends_at);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('billing.index')->with('error', 'We could not verify the payment. If you were charged, contact support.');
        }

        return redirect()->route('billing.index')->with('success', 'Payment received — your plan is now active.');
    }

    public function paypalReturn(Request $request)
    {
        $tenantId = $this->tenantId();
        $sub = Subscription::query()->forTenant($tenantId)->findOrFail($request->integer('sub'));

        if ($sub->isActive()) {
            return redirect()->route('billing.index')->with('success', 'Your subscription is active.');
        }

        try {
            if ($sub->is_recurring) {
                $paypalSub = PlatformPayPal::getSubscription((string) $sub->gateway_subscription_id);
                if (! PlatformPayPal::subscriptionIsActive($paypalSub)) {
                    return redirect()->route('billing.index')->with('error', 'PayPal subscription is not active yet.');
                }
                BillingManager::activatePaid($sub);
                BillingManager::recordPayment($sub, (float) $sub->amount, Subscription::GATEWAY_PAYPAL, $sub->gateway_subscription_id, $sub->starts_at, $sub->ends_at);
            } else {
                $capture = PlatformPayPal::captureOrder((string) $sub->gateway_reference);
                if (! PlatformPayPal::orderIsCompleted($capture)) {
                    return redirect()->route('billing.index')->with('error', 'PayPal payment was not completed.');
                }
                BillingManager::activatePaid($sub);
                BillingManager::recordPayment($sub, (float) $sub->amount, Subscription::GATEWAY_PAYPAL, PlatformPayPal::orderCaptureId($capture), $sub->starts_at, $sub->ends_at);
            }
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('billing.index')->with('error', 'We could not verify the PayPal payment. If you were charged, contact support.');
        }

        return redirect()->route('billing.index')->with('success', 'Payment received — your plan is now active.');
    }

    public function cancelCheckout(Request $request)
    {
        $tenantId = $this->tenantId();
        $sub = Subscription::query()->forTenant($tenantId)->find($request->integer('sub'));
        if ($sub && $sub->status === Subscription::STATUS_PENDING) {
            $sub->update(['status' => Subscription::STATUS_CANCELLED, 'cancelled_at' => now()]);
        }

        return redirect()->route('billing.index')->with('error', 'Checkout cancelled.');
    }

    public function cancel(Request $request)
    {
        $tenantId = $this->tenantId();
        $sub = BillingManager::activeFor($tenantId);
        if (! $sub) {
            return back()->with('error', 'No active subscription to cancel.');
        }

        // Stop recurring billing at the gateway, but let access run until period end.
        try {
            if ($sub->gateway === Subscription::GATEWAY_STRIPE && $sub->gateway_subscription_id) {
                PlatformPayments::cancelSubscription($sub->gateway_subscription_id);
            } elseif ($sub->gateway === Subscription::GATEWAY_PAYPAL && $sub->gateway_subscription_id) {
                PlatformPayPal::cancelSubscription($sub->gateway_subscription_id);
            }
        } catch (Throwable $e) {
            report($e);
        }

        // Keep access until the paid period ends; just stop auto-renew.
        $sub->update(['is_recurring' => false]);

        return back()->with('success', 'Auto-renew turned off. Your plan stays active until '.$sub->ends_at?->format('d M Y').'.');
    }
}
