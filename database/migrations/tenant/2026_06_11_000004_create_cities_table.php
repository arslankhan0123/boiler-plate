<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT-DB table: the cities a tenant has selected from the central `lookups`
 * master (type=city). Each row snapshots the master entry's OWN fields plus a
 * `lookup_id` back-reference and a `state_id` FK to its parent tenant state
 * (auto-created on pick); the country is reached via state→country. Parent data
 * is read through relations, never duplicated here. Plus the tenant-owned fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('lookup_id');
            $table->string('external_id', 40)->nullable(); // master source id (import round-trip)

            $table->string('name');
            $table->unsignedBigInteger('state_id'); // FK → tenant states.id (parent; country via state)
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
            $table->index('state_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
