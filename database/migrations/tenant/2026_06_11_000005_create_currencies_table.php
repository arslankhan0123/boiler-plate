<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT-DB table: the currencies a tenant has selected from the central `lookups`
 * master (type=currency). Each row is a SNAPSHOT of the picked master entry plus a
 * `lookup_id` back-reference and the tenant-owned fields. Flat (no parent).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('lookup_id');

            // Snapshot of the master entry (ISO-4217). `code` is the alpha code.
            $table->string('code', 3);
            $table->string('name');
            $table->string('symbol', 10)->nullable();
            $table->char('numeric_code', 3)->nullable();
            $table->unsignedTinyInteger('decimal_digits')->nullable();

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
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
