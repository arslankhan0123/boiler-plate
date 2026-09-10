<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT-DB table: the countries a tenant has selected from the central `lookups`
 * master. Each row is a SNAPSHOT of the picked master entry (so the tenant DB
 * never joins to the central DB), plus a `lookup_id` back-reference and the
 * tenant-owned fields (is_active / is_default / sort_order).
 *
 * Lives in the tenant migration path so it is created in each tenant database by
 * `tenants:migrate` / the provisioning pipeline — NOT in the central DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table): void {
            $table->id();

            // Back-reference to central lookups.id (cross-DB → FK-less). Uniqueness
            // among non-deleted rows is enforced in the service (so a soft-deleted
            // country can be re-added) — see CountryService.
            $table->unsignedBigInteger('lookup_id');

            // Snapshot of the master entry (ISO identity fields are locked / read-only).
            $table->string('name');
            $table->char('iso2', 2)->nullable();
            $table->char('iso3', 3)->nullable();
            $table->char('numeric_code', 3)->nullable();
            $table->string('phone_code', 10)->nullable();
            $table->string('capital')->nullable();
            $table->string('region')->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->string('currency_symbol', 10)->nullable();
            $table->string('locale', 20)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->string('flag_image', 2048)->nullable();

            // Tenant-owned, editable fields.
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            // Central user ids — FK-less (a tenant->central FK is impossible).
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('deleted_by_user_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('lookup_id');
            $table->index('is_active');
            $table->index('is_default');
            $table->index('region');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
