<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT-DB table: tenant-defined areas/localities under a tenant `city`. Unlike
 * the other Setup tables, areas are NOT snapshots of a central `lookups` master —
 * they are pure tenant-owned custom data (address, post code, contact, …). Each
 * row belongs to a tenant city (`city_id`); the city/state/country names are read
 * through the city→state→country relations, never duplicated here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table): void {
            $table->id();

            // Parent tenant city (indexed FK). city→state→country reached via relations.
            $table->unsignedBigInteger('city_id');

            // Tenant-owned custom fields.
            $table->string('name');
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('area_code', 32)->nullable(); // post / zip code
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('data')->nullable(); // arbitrary extra fields (overflow)

            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            // Central user ids — FK-less.
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('deleted_by_user_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('city_id');
            $table->index('name');
            $table->index('area_code');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
