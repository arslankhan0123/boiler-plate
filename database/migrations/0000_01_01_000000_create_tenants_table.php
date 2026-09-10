<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Central table: the registry of tenants. Each tenant owns its own database
     * (database-per-tenant). The string `id` is a sequential, zero-padded key
     * (001, 002, …) that forms the tenant database name (tenant_<id>_database);
     * `data` holds any stancl virtual-column overflow.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
            $table->json('data')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
