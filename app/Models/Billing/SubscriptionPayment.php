<?php

declare(strict_types=1);

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

/**
 * One charge against a subscription (initial payment or a recurring renewal).
 * Central database.
 *
 * @property int         $id
 * @property int|null    $subscription_id
 * @property string      $tenant_id
 * @property float       $amount
 * @property string      $currency
 * @property string|null $gateway
 * @property string|null $gateway_payment_id
 * @property string      $status
 */
class SubscriptionPayment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection', 'central');
    }

    public function subscription(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
