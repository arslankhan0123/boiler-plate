<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Central key/value settings for the platform (master admin) itself — e.g. the
 * platform's own Stripe / PayPal credentials used to charge tenants for their
 * subscriptions. Secret values are stored encrypted (see App\Support\Billing\
 * PlatformSettings). This mirrors the per-tenant tenant_settings table but lives
 * in the central database and is never tenant-scoped.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_settings')) {
            return;
        }
        Schema::create('platform_settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
