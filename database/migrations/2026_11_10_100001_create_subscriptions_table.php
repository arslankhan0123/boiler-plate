<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central table: a tenant's subscription (trial or paid) to a plan.
 *
 *  - type         trial | paid
 *  - billing_cycle monthly | yearly | null (trials have no cycle)
 *  - status       pending | active | expired | cancelled
 *  - modules      snapshot of the granted module codes at purchase time
 *  - is_recurring true only when a card was given AND recurring was kept on;
 *                 otherwise the subscription simply expires at ends_at
 *  - gateway      stripe | paypal | manual (manual = comped / activated by master)
 *
 * One tenant may have many rows over time; the latest active row drives access.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscriptions')) {
            return;
        }
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->increments('id');
            $t->string('tenant_id');
            $t->unsignedInteger('plan_id')->nullable();

            $t->string('type', 20)->default('paid');        // trial | paid
            $t->string('billing_cycle', 20)->nullable();    // monthly | yearly | null
            $t->string('status', 20)->default('pending');   // pending | active | expired | cancelled
            $t->json('modules')->nullable();                // snapshot of granted modules

            $t->decimal('amount', 10, 2)->default(0);
            $t->string('currency', 3)->default('USD');
            $t->boolean('is_recurring')->default(false);

            $t->string('gateway', 20)->nullable();          // stripe | paypal | manual
            $t->string('gateway_customer_id')->nullable();
            $t->string('gateway_subscription_id')->nullable();
            $t->string('gateway_reference')->nullable();    // checkout session / order id

            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamp('cancelled_at')->nullable();

            $t->unsignedBigInteger('created_by')->nullable(); // user who created it (master or tenant)

            $t->timestamps();

            $t->foreign('tenant_id')->references('id')->on('tenants')->onUpdate('cascade')->onDelete('cascade');
            $t->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
