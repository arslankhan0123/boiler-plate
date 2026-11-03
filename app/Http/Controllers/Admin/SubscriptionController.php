<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Tenant;
use App\Support\Billing\BillingManager;
use App\Support\Billing\PlatformPayments;
use App\Support\Billing\PlatformPayPal;
use App\Support\Billing\PlatformSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Platform-only: the master admin sees every tenant's billing state, grants
 * trials, subscribes a tenant to a plan directly (comped) or via a payment
 * link, and cancels subscriptions. Trials are a master-only concept — tenants
 * never see the trial option.
 */
class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $subscriptions = Subscription::query()
            ->with('plan')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $tenants = Tenant::query()->orderBy('name')->get(['id', 'name']);
        $tenantNames = $tenants->pluck('name', 'id')->all();
        $plans = Plan::query()->active()->ordered()->get();

        // Current active subscription per tenant for the overview.
        $activeByTenant = Subscription::query()->active()->get()->keyBy('tenant_id');

        return view('admin.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'tenants' => $tenants,
            'tenantNames' => $tenantNames,
            'plans' => $plans,
            'activeByTenant' => $activeByTenant,
            'trialDays' => Subscription::TRIAL_DAYS,
            'gatewaysReady' => PlatformSettings::anyGatewayReady(),
        ]);
    }

    /** Grant a trial to a tenant (optionally tied to a plan's modules). */
    public function grantTrial(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => ['required', Rule::exists(Tenant::class, 'id')],
            'days' => ['required', 'integer', Rule::in(Subscription::TRIAL_DAYS)],
            'plan_id' => ['nullable', Rule::exists(Plan::class, 'id')],
        ]);

        $plan = $data['plan_id'] ? Plan::query()->find($data['plan_id']) : null;

        BillingManager::startTrial(
            tenantId: $data['tenant_id'],
            days: (int) $data['days'],
            plan: $plan,
            createdBy: auth()->id(),
        );

        return back()->with('success', 'Trial granted for '.$data['days'].' day(s).');
    }

    /**
     * Master subscribes a tenant to a plan.
     *  - mode=direct : activate immediately, comped (no card).
     *  - mode=charge : create a payment link the tenant can pay.
     */
    public function subscribeTenant(Request $request)
    {
        $data = $request->validate([
            'tenant_id' => ['required', Rule::exists(Tenant::class, 'id')],
            'plan_id' => ['required', Rule::exists(Plan::class, 'id')],
            'cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'mode' => ['required', Rule::in(['direct', 'charge'])],
            'gateway' => ['nullable', Rule::in(['stripe', 'paypal'])],
        ]);

        $plan = Plan::query()->findOrFail($data['plan_id']);

        if ($data['mode'] === 'direct') {
            $sub = BillingManager::createPending(
                tenantId: $data['tenant_id'],
                plan: $plan,
                cycle: $data['cycle'],
                recurring: false,
                gateway: Subscription::GATEWAY_MANUAL,
                createdBy: auth()->id(),
            );
            BillingManager::activatePaid($sub);
            BillingManager::recordPayment($sub, 0.0, Subscription::GATEWAY_MANUAL, null, $sub->starts_at, $sub->ends_at, ['comped' => true, 'by' => auth()->id()]);

            return back()->with('success', 'Subscription activated for the tenant (comped, no charge).');
        }

        // mode=charge → produce a payment link.
        $gateway = $data['gateway'] ?: (PlatformSettings::stripeReady() ? 'stripe' : 'paypal');
        if (! PlatformSettings::anyGatewayReady()) {
            return back()->with('error', 'No payment gateway is configured. Add Stripe or PayPal keys in Billing settings first.');
        }

        try {
            $sub = BillingManager::createPending(
                tenantId: $data['tenant_id'],
                plan: $plan,
                cycle: $data['cycle'],
                recurring: true,
                gateway: $gateway,
                createdBy: auth()->id(),
            );

            $brand = \App\Support\Settings::get('company_name', 'Platform');
            $success = route('subscriptions.index');
            $cancel = route('subscriptions.index');

            if ($gateway === 'stripe') {
                $session = PlatformPayments::createCheckoutSession([
                    'tenant_id' => $data['tenant_id'],
                    'plan' => $plan,
                    'cycle' => $data['cycle'],
                    'recurring' => true,
                    'success_url' => $success.'?paid=1',
                    'cancel_url' => $cancel.'?cancelled=1',
                    'subscription_id' => $sub->id,
                ]);
                $sub->update(['gateway_reference' => $session['id']]);
                $link = $session['url'];
            } else {
                $res = PlatformPayPal::createSubscription($plan, $data['cycle'], $data['tenant_id'], $brand, $success.'?paid=1', $cancel.'?cancelled=1');
                $sub->update(['gateway_subscription_id' => $res['id']]);
                $link = $res['approve'];
            }
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Could not create payment link: '.$e->getMessage());
        }

        return back()->with('success', 'Payment link created — send it to the tenant:')->with('payment_link', $link);
    }

    public function cancel(Subscription $subscription)
    {
        // Best-effort cancel at the gateway too.
        try {
            if ($subscription->gateway === Subscription::GATEWAY_STRIPE && $subscription->gateway_subscription_id) {
                PlatformPayments::cancelSubscription($subscription->gateway_subscription_id);
            } elseif ($subscription->gateway === Subscription::GATEWAY_PAYPAL && $subscription->gateway_subscription_id) {
                PlatformPayPal::cancelSubscription($subscription->gateway_subscription_id);
            }
        } catch (Throwable $e) {
            report($e);
        }

        BillingManager::cancel($subscription, revokeNow: true);

        return back()->with('success', 'Subscription cancelled and access revoked.');
    }
}
