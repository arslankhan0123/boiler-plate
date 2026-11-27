<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Physical stocktake: a count sheet; finalising creates an adjustment for the differences. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_counts', function (Blueprint $t) {
            $t->id();
            $t->string('reference_no', 50)->unique();
            $t->foreignId('warehouse_id')->constrained('inv_warehouses');
            $t->date('date');
            $t->string('status', 10)->default('draft'); // draft | finalized
            $t->text('note')->nullable();
            $t->foreignId('adjustment_id')->nullable()->constrained('inv_adjustments')->nullOnDelete();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });
        Schema::create('inv_count_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('count_id')->constrained('inv_counts')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained('inv_items');
            $t->decimal('system_qty', 15, 3)->default(0); // snapshot when the sheet was built
            $t->decimal('counted_qty', 15, 3)->nullable(); // what was physically counted
            $t->string('note')->nullable();
            $t->timestamps();
            $t->unique(['count_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_count_items');
        Schema::dropIfExists('inv_counts');
    }
};
