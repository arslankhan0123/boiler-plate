<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TENANT-DB table: tenant-defined employees (core HR profile). Each belongs to a
 * tenant `department` + `designation` (both required) and optionally links to the
 * tenant's own `country`/`state` for the address. Pure tenant-owned custom data;
 * file/photo uploads, salary/attendance satellites and auto-code generation are
 * intentionally out of scope (overflow available via `data`). Related names are
 * read through relations, never duplicated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();

            $table->string('name');
            $table->string('code')->nullable(); // manual; CSV import match key

            // Required org links (FK-less, indexed).
            $table->unsignedBigInteger('department_id');
            $table->unsignedBigInteger('designation_id');

            // Contact + personal.
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->date('dob')->nullable();
            $table->string('gender', 16)->nullable();          // male/female/others
            $table->string('marital_status', 16)->nullable();  // married/unmarried/divorced
            $table->string('blood_group', 8)->nullable();
            $table->string('religion')->nullable();

            // Address (optional links to the tenant's own Setup tables).
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('zip', 32)->nullable();

            // Identification documents.
            $table->string('national_id')->nullable();
            $table->string('iqama_no')->nullable();
            $table->date('iqama_no_expiry_date')->nullable();
            $table->string('passport')->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->string('driving_license_no')->nullable();
            $table->date('driving_license_expiry_date')->nullable();
            $table->string('tuv_no')->nullable();
            $table->date('tuv_no_expiry_date')->nullable();

            // Employment.
            $table->date('join_date');
            $table->string('type')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('duty_type')->nullable();

            // Banking.
            $table->string('bank_name')->nullable();
            $table->string('bank_branch_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('iban_num')->nullable();
            $table->string('company_name')->nullable();

            // Compensation.
            $table->decimal('basic_salary', 15, 2)->nullable();
            $table->decimal('transport_allowance', 15, 2)->nullable();
            $table->decimal('gross_salary', 15, 2)->nullable();
            $table->decimal('hourly_rate', 15, 2)->nullable();

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
            $table->index('department_id');
            $table->index('designation_id');
            $table->index('country_id');
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
