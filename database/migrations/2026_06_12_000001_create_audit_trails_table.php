<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Central, app-level audit trail. One row per data-change event on an
     * auditable model — capturing the previous and new values. Written in the
     * background by the RecordAudit job, so it carries `tenant_id` plus an
     * FK-less morph (`auditable_type`/`auditable_id`) that may point at a record
     * in a tenant database (cross-DB → no foreign key). Rows are immutable:
     * `created_at` only, no `updated_at`.
     */
    public function up(): void
    {
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();

            $table->string('tenant_id')->nullable()->index();

            // The changed model/record. FK-less: may live in a tenant DB.
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');

            $table->string('event'); // created / updated / deleted / restored / hardDeleted
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('reason')->nullable();

            $table->unsignedBigInteger('user_id')->nullable()->index(); // central user

            // Correlates this data change to the request's activity_logs row
            // (one request -> one activity log -> many audit trails).
            $table->string('request_id')->nullable()->index();

            $table->timestamp('created_at')->nullable()->index();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['tenant_id', 'auditable_type', 'auditable_id'], 'audit_trails_tenant_morph_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};
