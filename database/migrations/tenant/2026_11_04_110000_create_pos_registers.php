<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** POS cash-drawer sessions: open with a float, close with a counted amount, over/short recorded. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rt_registers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('warehouse_id')->constrained('inv_warehouses');
            $t->unsignedBigInteger('opened_by');
            $t->unsignedBigInteger('closed_by')->nullable();
            $t->timestamp('opened_at');
            $t->timestamp('closed_at')->nullable();
            $t->string('status', 10)->default('open'); // open | closed
            $t->decimal('opening_cash', 15, 2)->default(0);
            // Filled at close:
            $t->decimal('counted_cash', 15, 2)->nullable();      // what the cashier counted in the drawer
            $t->decimal('expected_cash', 15, 2)->nullable();     // opening + cash sales - cash refunds
            $t->decimal('cash_sales', 15, 2)->nullable();
            $t->decimal('cash_refunds', 15, 2)->nullable();
            $t->decimal('other_sales', 15, 2)->nullable();       // non-cash collected
            $t->decimal('over_short', 15, 2)->nullable();        // counted - expected
            $t->decimal('cash_in', 15, 2)->default(0);           // manual pay-ins
            $t->decimal('cash_out', 15, 2)->default(0);          // manual pay-outs / drops
            $t->text('open_note')->nullable();
            $t->text('close_note')->nullable();
            $t->timestamps();
            $t->index(['status', 'opened_by']);
        });

        // Manual cash movements during a shift (pay-in / pay-out).
        Schema::create('rt_register_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('register_id')->constrained('rt_registers')->cascadeOnDelete();
            $t->string('direction', 3); // in | out
            $t->decimal('amount', 15, 2);
            $t->string('reason')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });

        // Link every POS invoice to the register it was rung on.
        Schema::table('rt_documents', fn (Blueprint $t) => $t->foreignId('register_id')->nullable()->after('salesman_id')->constrained('rt_registers')->nullOnDelete());
    }

    public function down(): void
    {
        Schema::table('rt_documents', function (Blueprint $t) {
            $t->dropConstrainedForeignId('register_id');
        });
        Schema::dropIfExists('rt_register_movements');
        Schema::dropIfExists('rt_registers');
    }
};
