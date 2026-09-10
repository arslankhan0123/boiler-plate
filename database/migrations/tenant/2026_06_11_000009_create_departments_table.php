<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT-DB table: tenant-defined departments. Pure tenant-owned custom data — a
 * flat list (no central master, no hierarchy in this scope).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->json('data')->nullable(); // arbitrary extra fields (overflow)

            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            // Central user ids — FK-less.
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('deleted_by_user_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('code');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
