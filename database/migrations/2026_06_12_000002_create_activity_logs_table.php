<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Central, app-level activity log — the "who did what, from where" record.
     * One row per API request (reads included, e.g. login/list), capturing the
     * request context (ip, geo-resolved country, device, url, response status).
     * The matching data changes (if any) live in `audit_trails`, correlated by
     * the shared `request_id` (one request -> one activity log -> many audit
     * trails). Morph (`subject_type`/`subject_id`) is FK-less (tenant DB).
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            $table->string('tenant_id')->nullable()->index();

            // The model acted on (optional; FK-less, may live in a tenant DB).
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            $table->unsignedBigInteger('user_id')->nullable()->index(); // central user
            $table->string('action'); // e.g. countries.update, auth.login

            $table->string('ip_address', 45)->nullable();
            $table->string('request_country')->nullable(); // geo from IP (MaxMind)
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('device_name')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('session_id')->nullable();
            // Correlation key shared with audit_trails for this request.
            $table->string('request_id')->nullable()->index();
            $table->string('method')->nullable();
            $table->text('url')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable(); // HTTP response status

            $table->timestamp('created_at')->nullable()->index();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['tenant_id', 'subject_type', 'subject_id'], 'activity_logs_tenant_subject_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
