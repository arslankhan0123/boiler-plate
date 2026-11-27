<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Accounting: chart of accounts, periods, journals + lines (double-entry). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acc_accounts', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->index();
            $t->string('name');
            $t->string('type', 12);                       // asset|liability|equity|income|expense
            $t->unsignedBigInteger('parent_id')->nullable()->index();
            $t->boolean('is_group')->default(false);      // a header/rollup account (no postings)
            $t->boolean('is_bank')->default(false);       // bank/cash account (shows in Banking)
            $t->string('control_type', 12)->nullable();   // receivable|payable (AR/AP control)
            $t->decimal('opening_balance', 16, 2)->default(0); // signed on the account's normal side
            $t->boolean('is_active')->default(true);
            $t->string('description')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });

        Schema::create('acc_periods', function (Blueprint $t) {
            $t->id();
            $t->string('name');                           // e.g. "FY2026 - Jan"
            $t->date('start_date');
            $t->date('end_date');
            $t->string('status', 10)->default('open');    // open|closed
            $t->timestamp('closed_at')->nullable();
            $t->unsignedBigInteger('closed_by')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });

        Schema::create('acc_journals', function (Blueprint $t) {
            $t->id();
            $t->string('reference', 30)->index();         // JV-0001
            $t->date('date');
            $t->string('memo')->nullable();
            $t->string('status', 10)->default('draft');   // draft|posted
            $t->string('source', 20)->default('manual');  // manual|opening|…
            $t->timestamp('posted_at')->nullable();
            $t->unsignedBigInteger('posted_by')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });

        Schema::create('acc_journal_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('journal_id')->index();
            $t->unsignedBigInteger('account_id')->index();
            $t->decimal('debit', 16, 2)->default(0);
            $t->decimal('credit', 16, 2)->default(0);
            $t->string('memo')->nullable();
            $t->boolean('reconciled')->default(false);
            $t->timestamp('reconciled_at')->nullable();
            $t->string('party_type', 12)->nullable();     // customer|supplier (for AR/AP subledger)
            $t->unsignedBigInteger('party_id')->nullable();
            $t->timestamps();
            $t->index(['account_id', 'reconciled']);
        });
    }

    public function down(): void
    {
        foreach (['acc_journal_lines', 'acc_journals', 'acc_periods', 'acc_accounts'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
