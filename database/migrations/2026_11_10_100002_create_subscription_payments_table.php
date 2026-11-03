<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central table: a ledger of every successful (or failed/refunded) charge against
 * a subscription — the initial payment and each recurring renewal captured via
 * the platform Stripe/PayPal webhooks. Used for the master admin's revenue view
 * and to extend a subscription's ends_at on renewal.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscription_payments')) {
            return;
        }
        Schema::create('subscription_payments', function (Blueprint $t) {
            $t->increments('id');
            $t->unsignedInteger('subscription_id')->nullable();
            $t->string('tenant_id');

            $t->decimal('amount', 10, 2)->default(0);
            $t->string('currency', 3)->default('USD');
            $t->string('gateway', 20)->nullable();           // stripe | paypal | manual
            $t->string('gateway_payment_id')->nullable();    // charge / capture id (idempotency)
            $t->string('status', 20)->default('paid');       // paid | failed | refunded

            $t->timestamp('period_start')->nullable();
            $t->timestamp('period_end')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->json('meta')->nullable();

            $t->timestamps();

            $t->index('tenant_id');
            $t->index('subscription_id');
            $t->index('gateway_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
