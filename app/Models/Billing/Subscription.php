<?php

declare(strict_types=1);

namespace App\Models\Billing;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * A tenant's subscription (trial or paid) to a plan. Central database.
 *
 * @property int         $id
 * @property string      $tenant_id
 * @property int|null    $plan_id
 * @property string      $type           trial | paid
 * @property string|null $billing_cycle  monthly | yearly | null
 * @property string      $status         pending | active | expired | cancelled
 * @property array|null  $modules
 * @property float       $amount
 * @property string      $currency
 * @property bool        $is_recurring
 * @property string|null $gateway        stripe | paypal | manual
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $cancelled_at
 */
class Subscription extends Model
{
    public const TYPE_TRIAL = 'trial';
    public const TYPE_PAID = 'paid';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    public const GATEWAY_STRIPE = 'stripe';
    public const GATEWAY_PAYPAL = 'paypal';
    public const GATEWAY_MANUAL = 'manual';

    /** Trial durations (days) the master admin may grant. 30 == "1 month". */
    public const TRIAL_DAYS = [1, 2, 3, 5, 7, 14, 30];

    protected $guarded = ['id'];

    protected $casts = [
        'modules' => 'array',
        'amount' => 'decimal:2',
        'is_recurring' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection', 'central');
    }

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    // ── State ────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        return $this->ends_at === null || $this->ends_at->isFuture();
    }

    public function onTrial(): bool
    {
        return $this->type === self::TYPE_TRIAL && $this->isActive();
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED
            || ($this->ends_at !== null && $this->ends_at->isPast() && $this->status !== self::STATUS_CANCELLED);
    }

    /** Whole days remaining before this subscription ends (0 when none/over). */
    public function daysLeft(): int
    {
        if ($this->ends_at === null) {
            return 0;
        }

        return max(0, (int) ceil(now()->floatDiffInDays($this->ends_at, false)));
    }

    /** @return array<int,string> granted module codes (snapshot, else plan's). */
    public function grantedModules(): array
    {
        if (is_array($this->modules) && $this->modules !== []) {
            return array_values($this->modules);
        }

        return $this->plan?->moduleCodes() ?? [];
    }

    public function label(): string
    {
        if ($this->type === self::TYPE_TRIAL) {
            return 'Trial';
        }

        $plan = $this->plan?->name ?? 'Plan';
        $cycle = $this->billing_cycle ? ucfirst($this->billing_cycle) : '';

        return trim("$plan $cycle");
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive($q)
    {
        return $q->where('status', self::STATUS_ACTIVE)
            ->where(function ($w) {
                $w->whereNull('ends_at')->orWhere('ends_at', '>', now());
            });
    }

    public function scopeForTenant($q, string $tenantId)
    {
        return $q->where('tenant_id', $tenantId);
    }
}
