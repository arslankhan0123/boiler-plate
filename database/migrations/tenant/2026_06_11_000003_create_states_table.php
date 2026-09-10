<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT-DB table: the states/provinces a tenant has selected from the central
 * `lookups` master (type=state). Each row snapshots the master entry's OWN fields
 * plus a `lookup_id` back-reference and a `country_id` FK to its parent tenant
 * country (auto-created on pick). Parent data is read through the relation, never
 * duplicated here. Plus the tenant-owned fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table): void {
            $table->id();

            // Back-reference to central lookups.id (cross-DB → FK-less). Uniqueness
            // among non-deleted rows is enforced in the service.
            $table->unsignedBigInteger('lookup_id');
            $table->string('external_id', 40)->nullable(); // master source id (import round-trip)

            // Snapshot of the master entry's own fields.
            $table->string('name');
            $table->string('code', 15)->nullable(); // state code
            $table->unsignedBigInteger('country_id'); // FK → tenant countries.id (parent)
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Tenant-owned, editable fields.
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            // Central user ids — FK-less.
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('deleted_by_user_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('lookup_id');
            $table->index('is_active');
            $table->index('is_default');
            $table->index('country_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
