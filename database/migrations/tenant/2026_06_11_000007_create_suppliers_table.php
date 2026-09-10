<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT-DB table: tenant-defined suppliers (vendors). Pure tenant-owned custom
 * data — NOT a snapshot of any central master. Optionally linked to the tenant's
 * own `countries` / `states` / `currencies` (FK-less, indexed); those names are
 * read through relations, never duplicated here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();

            $table->string('company_name');
            $table->string('vat_number')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('whatsapp', 32)->nullable();
            $table->string('website')->nullable();
            $table->string('group_name')->nullable(); // free-text grouping label

            // Optional links to the tenant's own Setup tables (FK-less, indexed).
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->string('default_language', 8)->nullable();

            // Address detail (free-text).
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('zip', 32)->nullable();
            $table->string('po_box', 32)->nullable();
            $table->string('mailing_address')->nullable();

            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->json('data')->nullable(); // arbitrary extra fields (overflow)

            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            // Central user ids — FK-less.
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('deleted_by_user_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('company_name');
            $table->index('group_name');
            $table->index('currency_id');
            $table->index('country_id');
            $table->index('state_id');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
