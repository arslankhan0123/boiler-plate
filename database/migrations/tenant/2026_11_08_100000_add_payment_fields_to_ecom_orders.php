<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Online payments: track the Stripe PaymentIntent / PayPal order id and when the order was paid. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecom_orders', function (Blueprint $t) {
            if (! Schema::hasColumn('ecom_orders', 'payment_intent_id')) {
                $t->string('payment_intent_id')->nullable()->index()->after('payment_method');
            }
            if (! Schema::hasColumn('ecom_orders', 'paid_at')) {
                $t->timestamp('paid_at')->nullable()->after('placed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ecom_orders', function (Blueprint $t) {
            if (Schema::hasColumn('ecom_orders', 'payment_intent_id')) {
                $t->dropColumn('payment_intent_id');
            }
            if (Schema::hasColumn('ecom_orders', 'paid_at')) {
                $t->dropColumn('paid_at');
            }
        });
    }
};
