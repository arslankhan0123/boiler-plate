<?php

declare(strict_types=1);

namespace App\Models\Billing;

use App\Support\ModuleRegistry;
use Illuminate\Database\Eloquent\Model;

/**
 * A subscription plan sold by the platform (master admin) to tenants.
 * Lives in the central database; always resolved on the central connection even
 * when the current request is running inside a tenant context.
 *
 * @property int         $id
 * @property string      $name
 * @property string      $slug
 * @property array       $modules
 * @property float       $price_monthly
 * @property float       $price_yearly
 * @property string      $currency
 * @property bool        $is_active
 * @property bool        $is_public
 */
class Plan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'modules' => 'array',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Always read/write plans from the central DB, even under an active tenant. */
    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection', 'central');
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // ── Pricing ──────────────────────────────────────────────────────────

    /** Price for a billing cycle ('monthly' | 'yearly'). */
    public function priceFor(string $cycle): float
    {
        return (float) ($cycle === 'yearly' ? $this->price_yearly : $this->price_monthly);
    }

    public function isFree(): bool
    {
        return (float) $this->price_monthly <= 0 && (float) $this->price_yearly <= 0;
    }

    /** Effective monthly cost when billed yearly (for the "save X%" hint). */
    public function yearlyMonthlyEquivalent(): float
    {
        return round(((float) $this->price_yearly) / 12, 2);
    }

    /** Percentage saved by paying yearly vs 12× monthly (0 when n/a). */
    public function yearlySavingPercent(): int
    {
        $full = ((float) $this->price_monthly) * 12;
        if ($full <= 0 || (float) $this->price_yearly <= 0) {
            return 0;
        }

        return (int) round((1 - ((float) $this->price_yearly / $full)) * 100);
    }

    // ── Modules ──────────────────────────────────────────────────────────

    /** @return array<int,string> */
    public function moduleCodes(): array
    {
        return array_values((array) ($this->modules ?? []));
    }

    /** @return array<int,string> human module names, resolved from the registry */
    public function moduleNames(): array
    {
        $out = [];
        foreach ($this->moduleCodes() as $code) {
            $out[] = ModuleRegistry::get($code)['name'] ?? ucfirst($code);
        }

        return $out;
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopePublic($q)
    {
        return $q->where('is_active', true)->where('is_public', true);
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order')->orderBy('price_monthly');
    }
}
