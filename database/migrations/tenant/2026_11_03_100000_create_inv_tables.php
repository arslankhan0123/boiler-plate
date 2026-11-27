<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retail inventory: item masters, warehouses, warehouse-wise stock with a movement ledger,
 * adjustments / damaged entries and stock transfers.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Simple masters in one table: department, category (parent=department), subcategory (parent=category),
        // brand, unit, color, size, rack, bin, sub_bin (parent=bin), variant.
        Schema::create('inv_attributes', function (Blueprint $t) {
            $t->id();
            $t->string('type', 20)->index();
            $t->foreignId('parent_id')->nullable()->constrained('inv_attributes')->nullOnDelete();
            $t->string('code', 50)->nullable();
            $t->string('name');
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['type', 'name']);
        });

        Schema::create('inv_warehouses', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('type', 20)->default('warehouse'); // warehouse | shop | system
            $t->string('contact_person')->nullable();
            $t->string('mobile', 50)->nullable();
            $t->string('phone', 50)->nullable();
            $t->string('email')->nullable();
            $t->string('city')->nullable();
            $t->string('area')->nullable();
            $t->text('address')->nullable();
            $t->boolean('is_default')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('inv_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('parent_id')->nullable()->constrained('inv_items')->cascadeOnDelete(); // variant children
            $t->string('item_group', 10)->default('single'); // single | variant
            $t->boolean('is_service')->default(false);
            $t->string('code', 50)->unique();
            $t->string('name');
            $t->string('sku', 100)->nullable();
            $t->string('hsn', 50)->nullable();
            $t->string('barcode', 100)->nullable()->index();
            foreach (['department', 'category', 'subcategory', 'brand', 'unit', 'color', 'size', 'rack', 'bin', 'sub_bin', 'variant'] as $a) {
                $t->foreignId($a.'_id')->nullable()->constrained('inv_attributes')->nullOnDelete();
            }
            $t->unsignedBigInteger('supplier_id')->nullable();
            $t->decimal('alert_qty', 15, 3)->default(0);
            $t->decimal('min_qty', 15, 3)->nullable();
            $t->decimal('max_qty', 15, 3)->nullable();
            $t->decimal('reorder_qty', 15, 3)->nullable();
            $t->string('lot_number', 100)->nullable();
            $t->date('expire_date')->nullable();
            // Pricing: price = cost before tax; purchase_price = cost incl. tax (exclusive) or same (inclusive)
            $t->decimal('price', 15, 4)->default(0);
            $t->unsignedBigInteger('tax_id')->nullable();
            $t->string('tax_type', 10)->default('exclusive'); // exclusive | inclusive
            $t->decimal('purchase_price', 15, 4)->default(0);
            $t->decimal('profit_margin', 9, 4)->nullable();
            $t->decimal('sales_price', 15, 4)->default(0);
            $t->decimal('mrp', 15, 4)->nullable();
            $t->string('discount_type', 10)->default('percentage'); // percentage | fixed
            $t->decimal('discount', 15, 4)->default(0);
            $t->decimal('seller_points', 15, 4)->default(0);
            $t->decimal('opening_stock', 15, 3)->default(0);
            $t->decimal('stock', 15, 3)->default(0); // total across warehouses (cache)
            $t->string('image')->nullable();
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('inv_stocks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $t->decimal('qty', 15, 3)->default(0);
            $t->timestamps();
            $t->unique(['warehouse_id', 'item_id']);
        });

        // Every stock change; qty is signed (+ in / - out).
        Schema::create('inv_stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('warehouse_id')->constrained('inv_warehouses')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained('inv_items')->cascadeOnDelete();
            $t->string('type', 30); // opening, purchase, purchase_return, sale, sale_return, adjustment, damage, transfer_in, transfer_out
            $t->decimal('qty', 15, 3);
            $t->nullableMorphs('reference');
            $t->date('date');
            $t->string('note')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->index(['item_id', 'date']);
        });

        // Stock adjustment (+/-) and damaged items (kind=damage, always out).
        Schema::create('inv_adjustments', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 10)->default('adjustment'); // adjustment | damage
            $t->string('reference_no', 50)->unique();
            $t->foreignId('warehouse_id')->constrained('inv_warehouses');
            $t->date('date');
            $t->text('note')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });
        Schema::create('inv_adjustment_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('adjustment_id')->constrained('inv_adjustments')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained('inv_items');
            $t->decimal('qty', 15, 3); // signed for adjustments, positive for damage
            $t->string('description')->nullable();
            $t->timestamps();
        });

        Schema::create('inv_transfers', function (Blueprint $t) {
            $t->id();
            $t->string('reference_no', 50)->unique();
            $t->foreignId('from_warehouse_id')->constrained('inv_warehouses');
            $t->foreignId('to_warehouse_id')->constrained('inv_warehouses');
            $t->date('date');
            $t->text('note')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });
        Schema::create('inv_transfer_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transfer_id')->constrained('inv_transfers')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained('inv_items');
            $t->decimal('qty', 15, 3);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['inv_transfer_items', 'inv_transfers', 'inv_adjustment_items', 'inv_adjustments', 'inv_stock_movements', 'inv_stocks', 'inv_items', 'inv_warehouses', 'inv_attributes'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
