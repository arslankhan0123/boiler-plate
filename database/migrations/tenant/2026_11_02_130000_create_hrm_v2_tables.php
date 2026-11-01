<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HRM v2: shifts, salary components, loans, performance reviews,
 * onboarding/offboarding checklists, exits and announcements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hrm_shifts', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->time('start_time');
            $t->time('end_time');
            $t->unsignedSmallInteger('grace_minutes')->default(15);
            $t->unsignedSmallInteger('half_day_minutes')->default(240);
            $t->boolean('is_default')->default(false);
            $t->timestamps();
        });

        Schema::table('employees', function (Blueprint $t) {
            if (! Schema::hasColumn('employees', 'shift_id')) {
                $t->unsignedBigInteger('shift_id')->nullable()->after('designation_id')->index();
            }
        });

        // Recurring monthly earnings / deductions per employee (e.g. house rent, tax, EOBI).
        Schema::create('hrm_salary_components', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id')->index();
            $t->string('name');
            $t->string('type', 12);             // earning|deduction
            $t->decimal('amount', 15, 2);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('hrm_loans', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id')->index();
            $t->string('type', 12)->default('loan');     // loan|advance
            $t->decimal('amount', 15, 2);
            $t->decimal('installment', 15, 2);
            $t->decimal('repaid', 15, 2)->default(0);
            $t->date('start_month');                     // first month deducted
            $t->string('status', 12)->default('active'); // active|closed
            $t->string('reason')->nullable();
            $t->unsignedBigInteger('approved_by')->nullable();
            $t->timestamps();
        });

        // Breakdown lines on each payslip (components + loan installments).
        Schema::create('hrm_payslip_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('payslip_id')->index();
            $t->string('type', 12);            // earning|deduction
            $t->string('label');
            $t->decimal('amount', 15, 2);
            $t->unsignedBigInteger('loan_id')->nullable();
            $t->timestamps();
        });

        Schema::table('hrm_payslips', function (Blueprint $t) {
            if (! Schema::hasColumn('hrm_payslips', 'component_earnings')) {
                $t->decimal('component_earnings', 15, 2)->default(0)->after('bonus');
                $t->decimal('component_deductions', 15, 2)->default(0)->after('other_deductions');
                $t->decimal('loan_deductions', 15, 2)->default(0)->after('component_deductions');
            }
        });

        Schema::create('hrm_reviews', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id')->index();
            $t->unsignedBigInteger('reviewer_id')->nullable()->index(); // employees.id
            $t->string('reviewer_name')->nullable();
            $t->string('period');                     // e.g. "2026 H2", "Q4 2026"
            $t->json('scores')->nullable();           // criterion => 1..5
            $t->decimal('overall', 3, 1)->nullable();
            $t->text('strengths')->nullable();
            $t->text('improvements')->nullable();
            $t->text('goals')->nullable();
            $t->string('status', 16)->default('draft'); // draft|shared|acknowledged
            $t->text('employee_comment')->nullable();
            $t->timestamp('shared_at')->nullable();
            $t->timestamp('acknowledged_at')->nullable();
            $t->timestamps();
        });

        Schema::create('hrm_checklist_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id')->index();
            $t->string('type', 12);            // onboarding|offboarding
            $t->string('title');
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamp('done_at')->nullable();
            $t->string('done_by')->nullable();
            $t->timestamps();
        });

        Schema::create('hrm_exits', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('employee_id')->index();
            $t->string('type', 16);            // resignation|termination|retirement|contract_end
            $t->date('notice_date');
            $t->date('last_working_day');
            $t->text('reason')->nullable();
            $t->string('status', 16)->default('in_progress'); // in_progress|completed|withdrawn
            $t->text('exit_interview')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('hrm_announcements', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('body');
            $t->unsignedBigInteger('department_id')->nullable(); // null = everyone
            $t->boolean('is_pinned')->default(false);
            $t->date('expires_at')->nullable();
            $t->string('author_name')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['hrm_announcements', 'hrm_exits', 'hrm_checklist_items', 'hrm_reviews', 'hrm_payslip_lines', 'hrm_loans', 'hrm_salary_components', 'hrm_shifts'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('hrm_payslips', fn (Blueprint $t) => $t->dropColumn(['component_earnings', 'component_deductions', 'loan_deductions']));
        Schema::table('employees', fn (Blueprint $t) => $t->dropColumn('shift_id'));
    }
};
