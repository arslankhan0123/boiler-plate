<?php

declare(strict_types=1);

namespace App\Support\Billing;

use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Billing\SubscriptionPayment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The brain of the subscription system. Everything that changes a tenant's
 * billing state goes through here so the two side effects that matter —
 * the subscription row and the tenant's enabled modules — never drift apart.
 *
 * tenant_modules lives in the central DB (keyed by tenant_id), so applying a
 * plan's modules is a plain central write via Tenant::syncModules(); no tenancy
 * switch is needed.
 */
class BillingManager
{
    /** The currently active subscription for a tenant, if any. */
    public static function activeFor(string $tenantId): ?Subscription
    {
        return Subscription::query()
            ->forTenant($tenantId)
            ->active()
            ->orderByDesc('ends_at')
            ->orderByDesc('id')
            ->first();
    }

    /** True when the tenant may use its business modules right now. */
    public static function hasAccess(string $tenantId): bool
    {
        return self::activeFor($tenantId) !== null;
    }

    // ── Period maths ─────────────────────────────────────────────────────

    public static function addCycle(Carbon $from, string $cycle): Carbon
    {
        return $cycle === 'yearly' ? $from->copy()->addYear() : $from->copy()->addMonth();
    }

    // ── Trials (master admin) ────────────────────────────────────────────

    /**
     * Grant a trial to a tenant. Modules come from $plan when given, otherwise
     * from $modules (e.g. "all business modules" for a blanket trial).
     *
     * @param array<int,string>|null $modules
     */
    public static function startTrial(string $tenantId, int $days, ?Plan $plan = null, ?array $modules = null, ?int $createdBy = null): Subscription
    {
        $mods = $plan?->moduleCodes() ?? $modules ?? array_keys(\App\Support\ModuleRegistry::assignable());
        $now = now();

        $sub = new Subscription([
            'tenant_id' => $tenantId,
            'plan_id' => $plan?->id,
            'type' => Subscription::TYPE_TRIAL,
            'billing_cycle' => null,
            'status' => Subscription::STATUS_ACTIVE,
            'modules' => array_values($mods),
            'amount' => 0,
            'currency' => $plan?->currency ?? PlatformSettings::currency(),
            'is_recurring' => false,
            'gateway' => Subscription::GATEWAY_MANUAL,
            'starts_at' => $now,
            'ends_at' => $now->copy()->addDays(max(1, $days)),
            'created_by' => $createdBy,
        ]);
        $sub->save();

        self::applyActive($sub);

        return $sub;
    }

    // ── Pending subscription (before payment) ────────────────────────────

    /** A pending paid subscription awaiting gateway confirmation. */
    public static function createPending(string $tenantId, Plan $plan, string $cycle, bool $recurring, string $gateway, ?int $createdBy = null): Subscription
    {
        $cycle = $cycle === 'yearly' ? 'yearly' : 'monthly';

        $sub = new Subscription([
            'tenant_id' => $tenantId,
            'plan_id' => $plan->id,
            'type' => Subscription::TYPE_PAID,
            'billing_cycle' => $cycle,
            'status' => Subscription::STATUS_PENDING,
            'modules' => $plan->moduleCodes(),
            'amount' => $plan->priceFor($cycle),
            'currency' => $plan->currency ?: PlatformSettings::currency(),
            'is_recurring' => $recurring,
            'gateway' => $gateway,
            'created_by' => $createdBy,
        ]);
        $sub->save();

        return $sub;
    }

    // ── Activation ───────────────────────────────────────────────────────

    /**
     * Activate a paid subscription (after payment confirmed, or comped by the
     * master admin). Sets the period and applies modules.
     */
    public static function activatePaid(Subscription $sub, ?Carbon $start = null): void
    {
        $start ??= now();
        $sub->status = Subscription::STATUS_ACTIVE;
        $sub->starts_at = $start;
        // Even recurring subs get a concrete ends_at; webhooks push it forward on renewal.
        $sub->ends_at = self::addCycle($start, $sub->billing_cycle ?: 'monthly');
        $sub->save();

        self::applyActive($sub);
    }

    /**
     * Make $sub the tenant's single active subscription: supersede any other
     * active rows and sync the tenant's modules to this subscription's grant.
     */
    public static function applyActive(Subscription $sub): void
    {
        DB::connection($sub->getConnectionName())->transaction(function () use ($sub) {
            Subscription::query()
                ->forTenant($sub->tenant_id)
                ->where('id', '!=', $sub->id)
                ->where('status', Subscription::STATUS_ACTIVE)
                ->update(['status' => Subscription::STATUS_EXPIRED, 'updated_at' => now()]);
        });

        $tenant = Tenant::query()->find($sub->tenant_id);
        $tenant?->syncModules($sub->grantedModules());
    }

    // ── Payments ledger ──────────────────────────────────────────────────

    public static function recordPayment(Subscription $sub, float $amount, string $gateway, ?string $paymentId, ?Carbon $periodStart = null, ?Carbon $periodEnd = null, array $meta = []): SubscriptionPayment
    {
        return SubscriptionPayment::query()->create([
            'subscription_id' => $sub->id,
            'tenant_id' => $sub->tenant_id,
            'amount' => $amount,
            'currency' => $sub->currency,
            'gateway' => $gateway,
            'gateway_payment_id' => $paymentId,
            'status' => 'paid',
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'paid_at' => now(),
            'meta' => $meta ?: null,
        ]);
    }

    /** Push a recurring subscription's period forward (called from webhooks). */
    public static function renew(Subscription $sub): void
    {
        $from = $sub->ends_at && $sub->ends_at->isFuture() ? $sub->ends_at : now();
        $sub->status = Subscription::STATUS_ACTIVE;
        $sub->ends_at = self::addCycle($from, $sub->billing_cycle ?: 'monthly');
        $sub->save();

        $tenant = Tenant::query()->find($sub->tenant_id);
        if ($tenant && ! $tenant->hasModule((string) ($sub->grantedModules()[0] ?? '__none__'))) {
            $tenant->syncModules($sub->grantedModules());
        }
    }

    // ── Expiry / cancellation ────────────────────────────────────────────

    public static function cancel(Subscription $sub, bool $revokeNow = false): void
    {
        $sub->status = Subscription::STATUS_CANCELLED;
        $sub->cancelled_at = now();
        if ($revokeNow) {
            $sub->ends_at = now();
        }
        $sub->save();

        if ($revokeNow) {
            self::revokeIfNoActive($sub->tenant_id);
        }
    }

    public static function expire(Subscription $sub): void
    {
        if ($sub->status !== Subscription::STATUS_CANCELLED) {
            $sub->status = Subscription::STATUS_EXPIRED;
            $sub->save();
        }
        self::revokeIfNoActive($sub->tenant_id);
    }

    /** Remove business modules from a tenant when it has no active subscription. */
    public static function revokeIfNoActive(string $tenantId): void
    {
        if (self::activeFor($tenantId) !== null) {
            return;
        }
        Tenant::query()->find($tenantId)?->syncModules([]);
    }
}
