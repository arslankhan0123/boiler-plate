<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT-DB table: documents attached to a tenant `employee` (metadata only —
 * `file` holds a filename/reference; actual upload/storage handling is out of
 * scope). Managed through the Employee payload (replace-sync), not a standalone
 * resource.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('employee_id');
            $table->string('name');
            $table->string('file')->nullable();      // filename/reference (no storage handling)
            $table->date('expiry_date')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('deleted_by_user_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
