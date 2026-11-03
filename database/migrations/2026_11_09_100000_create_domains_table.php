<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Custom domains for tenant storefronts (central DB). A tenant can point its own
 * domain (shop.brand.com or brand.com) at the platform; once DNS-verified, the
 * public store is served on that domain. Used by stancl/tenancy domain features.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('domains')) {
            return;
        }
        Schema::create('domains', function (Blueprint $t) {
            $t->increments('id');
            $t->string('domain', 255)->unique();
            $t->string('tenant_id');
            $t->string('token', 64)->nullable();        // DNS TXT verification token
            $t->timestamp('verified_at')->nullable();    // null = pending verification
            $t->timestamps();

            $t->foreign('tenant_id')->references('id')->on('tenants')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
