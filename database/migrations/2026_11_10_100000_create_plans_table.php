<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central table: subscription plans the master (platform) admin sells to tenants.
 * A plan is a named bundle of business modules with a monthly and a yearly price.
 * When a tenant's subscription for a plan is active, the plan's modules are
 * synced onto that tenant (tenant_modules). Stripe/PayPal product & price ids are
 * filled in when the plan is mirrored onto the gateway for recurring billing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('plans')) {
            return;
        }
        Schema::create('plans', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->json('modules');                       // ["sales","inventory",...]
            $t->decimal('price_monthly', 10, 2)->default(0);
            $t->decimal('price_yearly', 10, 2)->default(0);
            $t->string('currency', 3)->default('USD');
            $t->boolean('is_active')->default(true);   // plan usable at all
            $t->boolean('is_public')->default(true);   // shown to tenants on the billing page
            $t->unsignedInteger('sort_order')->default(0);

            // Stripe dynamic objects (for recurring card subscriptions)
            $t->string('stripe_product_id')->nullable();
            $t->string('stripe_price_monthly_id')->nullable();
            $t->string('stripe_price_yearly_id')->nullable();

            // PayPal dynamic objects (billing product + plans)
            $t->string('paypal_product_id')->nullable();
            $t->string('paypal_plan_monthly_id')->nullable();
            $t->string('paypal_plan_yearly_id')->nullable();

            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
