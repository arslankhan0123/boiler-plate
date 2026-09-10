<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CENTRAL master reference table (one row per dataset entry, e.g. every country
 * in the world). Generic: the `type` column distinguishes data sets (country /
 * continent / currency / language / status / …). Tenants read this to "search &
 * pick" the entries they want; the picked entries are snapshotted into each
 * tenant's own `countries` table.
 *
 * This migration lives in the module dir, so it is auto-discovered as a CENTRAL
 * migration (run by `php artisan migrate`), NOT a tenant migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lookups', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 40);                 // discriminator: country, continent, currency, …
            $table->unsignedBigInteger('parent_id')->nullable(); // self-reference (e.g. country -> continent)
            $table->string('code', 15)->nullable();     // generic code (for countries: ISO-2)
            $table->string('name');
            $table->string('image', 2048)->nullable();  // flag / icon URL or path

            // Country-specific attributes (populated only for type=country).
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

            // Stable source-dataset identity: dr5hn id for country/state/city; ISO-4217
            // alpha for currency. This — NOT `code` — is the canonical per-type key,
            // because state codes are not globally unique and cities have no code.
            $table->string('external_id', 40)->nullable();

            // Geo coordinates (type=state, type=city).
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Currency minor-unit digits (type=currency). Other currency facets reuse
            // existing columns: name=full name, code=currency_code=alpha, currency_symbol,
            // numeric_code.
            $table->unsignedTinyInteger('decimal_digits')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            // Canonical identity + the upsert conflict target for all seeders.
            $table->unique(['type', 'external_id']);
            // NOT unique: `code` (e.g. state_code) is not globally unique — identity is
            // (type, external_id). Kept as a plain index for lookups/search by code.
            $table->index(['type', 'code']);
            $table->index(['type', 'name']);
            $table->index(['type', 'iso2']);
            $table->index(['type', 'is_active']);
            $table->index('parent_id');
            // "Children of X" — states of a country, cities of a state (at 150k scale).
            $table->index(['type', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lookups');
    }
};
