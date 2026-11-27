<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HRM tables inside each tenant database.
 * Existing tenants: php artisan tenants:migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            if (! Schema::hasColumn('employees', 'user_id')) {
                $t->unsignedBigInteger('user_id')->nullable()->after('code')->index(); // central users.id
            }
        });

        Schema::table('designations', function (Blueprint $t) {
            if (! Schema::hasColumn('designations', 'parent_id')) {
                $t->unsignedBigInteger('parent_id')->nullable()->after('department_id')->index(); // reports-to
            }
        });

        Schema::create('hrm_leave_types', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code', 20)->nullable();
            $t->decimal('days_per_year', 6, 2)->default(0);
            $t->boolean('is_paid')->default(true);
            $t->boolean('carry_forward')->default(false);
            $t->decimal('max_carry_forward', 6, 2)->default(0);
            $t->string('color', 20)->default('#6366f1');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('hrm_leave_allocations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id')->index();
            $t->unsignedBigInteger('leave_type_id')->index();
            $t->unsignedSmallInteger('year');
            $t->decimal('allocated', 6, 2)->default(0);
            $t->decimal('carried_forward', 6, 2)->default(0);
            $t->timestamps();
            $t->unique(['employee_id', 'leave_type_id', 'year'], 'hrm_alloc_unique');
        });

        Schema::create('hrm_leave_requests', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id')->index();
            $t->unsignedBigInteger('leave_type_id')->index();
            $t->date('start_date');
            $t->date('end_date');
            $t->decimal('days', 6, 2);
            $t->text('reason')->nullable();
            $t->string('status', 20)->default('pending')->index(); // pending|approved|rejected|cancelled
            $t->unsignedBigInteger('reviewed_by')->nullable();     // central users.id
            $t->timestamp('reviewed_at')->nullable();
            $t->text('review_note')->nullable();
            $t->timestamps();
        });

        Schema::create('hrm_holidays', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->date('date')->unique();
            $t->timestamps();
        });

        Schema::create('hrm_attendances', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id')->index();
            $t->date('date')->index();
            $t->time('check_in')->nullable();
            $t->time('check_out')->nullable();
            $t->string('status', 20)->default('present'); // present|late|half_day|absent|on_leave|holiday
            $t->unsignedInteger('worked_minutes')->default(0);
            $t->string('note')->nullable();
            $t->unsignedBigInteger('marked_by')->nullable();
            $t->timestamps();
            $t->unique(['employee_id', 'date']);
        });

        Schema::create('hrm_payroll_runs', function (Blueprint $t) {
            $t->id();
            $t->string('month', 7)->unique();            // YYYY-MM
            $t->string('status', 20)->default('draft');   // draft|finalized|paid
            $t->unsignedSmallInteger('working_days')->default(0);
            $t->decimal('total_gross', 15, 2)->default(0);
            $t->decimal('total_deductions', 15, 2)->default(0);
            $t->decimal('total_net', 15, 2)->default(0);
            $t->unsignedBigInteger('processed_by')->nullable();
            $t->timestamp('finalized_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });

        Schema::create('hrm_payslips', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('payroll_run_id')->index();
            $t->unsignedBigInteger('employee_id')->index();
            $t->string('number')->unique();
            $t->decimal('basic_salary', 15, 2)->default(0);
            $t->decimal('allowances', 15, 2)->default(0);
            $t->decimal('bonus', 15, 2)->default(0);
            $t->decimal('unpaid_days', 6, 2)->default(0);
            $t->decimal('unpaid_deduction', 15, 2)->default(0);
            $t->decimal('other_deductions', 15, 2)->default(0);
            $t->decimal('gross', 15, 2)->default(0);
            $t->decimal('net', 15, 2)->default(0);
            $t->unsignedSmallInteger('present_days')->default(0);
            $t->string('note')->nullable();
            $t->timestamps();
            $t->unique(['payroll_run_id', 'employee_id']);
        });

        Schema::create('hrm_jobs', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->unsignedBigInteger('department_id')->nullable();
            $t->unsignedBigInteger('designation_id')->nullable();
            $t->string('employment_type', 30)->default('full_time');
            $t->string('location')->nullable();
            $t->unsignedSmallInteger('positions')->default(1);
            $t->decimal('salary_min', 15, 2)->nullable();
            $t->decimal('salary_max', 15, 2)->nullable();
            $t->text('description')->nullable();
            $t->text('requirements')->nullable();
            $t->string('status', 20)->default('draft'); // draft|open|closed
            $t->date('closes_at')->nullable();
            $t->timestamps();
        });

        Schema::create('hrm_job_applications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('job_id')->index();
            $t->string('name');
            $t->string('email');
            $t->string('phone', 32)->nullable();
            $t->string('resume')->nullable();       // storage path (tenants/{id}/hrm/resumes/...)
            $t->text('cover_letter')->nullable();
            $t->string('status', 20)->default('new')->index(); // new|shortlisted|interview|offered|hired|rejected
            $t->unsignedTinyInteger('rating')->nullable();
            $t->dateTime('interview_at')->nullable();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('employee_id')->nullable(); // set when hired
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['hrm_job_applications', 'hrm_jobs', 'hrm_payslips', 'hrm_payroll_runs', 'hrm_attendances',
            'hrm_holidays', 'hrm_leave_requests', 'hrm_leave_allocations', 'hrm_leave_types'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
