<?php

declare(strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', fn (Blueprint $t) => [$t->id(), $t->foreignId('warehouse_id')->constrained('inventory_warehouses'), $t->string('number')->unique(), $t->string('supplier_name'), $t->string('supplier_invoice')->nullable(), $t->string('status')->default('draft'), $t->decimal('subtotal', 15, 2), $t->decimal('tax_total', 15, 2)->default(0), $t->decimal('discount_total', 15, 2)->default(0), $t->decimal('grand_total', 15, 2), $t->timestamp('received_at')->nullable(), $t->text('notes')->nullable(), $t->unsignedBigInteger('created_by')->nullable(), $t->timestamps()]);
        Schema::create('purchase_items', fn (Blueprint $t) => [$t->id(), $t->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete(), $t->unsignedBigInteger('product_id'), $t->string('name'), $t->string('sku')->nullable(), $t->unsignedInteger('quantity'), $t->decimal('unit_cost', 15, 2), $t->decimal('tax_rate', 5, 2)->default(0), $t->decimal('tax_total', 15, 2)->default(0), $t->decimal('discount_total', 15, 2)->default(0), $t->decimal('line_total', 15, 2), $t->timestamps()]);
        Schema::create('sale_orders', fn (Blueprint $t) => [$t->id(), $t->foreignId('warehouse_id')->constrained('inventory_warehouses'), $t->string('number')->unique(), $t->string('customer_name')->nullable(), $t->string('customer_phone')->nullable(), $t->string('status')->default('draft'), $t->string('payment_status')->default('unpaid'), $t->decimal('subtotal', 15, 2), $t->decimal('tax_total', 15, 2)->default(0), $t->decimal('discount_total', 15, 2)->default(0), $t->decimal('grand_total', 15, 2), $t->text('notes')->nullable(), $t->unsignedBigInteger('created_by')->nullable(), $t->timestamps()]);
        Schema::create('sale_items', fn (Blueprint $t) => [$t->id(), $t->foreignId('sale_order_id')->constrained('sale_orders')->cascadeOnDelete(), $t->unsignedBigInteger('product_id'), $t->string('name'), $t->string('sku')->nullable(), $t->unsignedInteger('quantity'), $t->decimal('unit_price', 15, 2), $t->decimal('tax_rate', 5, 2)->default(0), $t->decimal('tax_total', 15, 2)->default(0), $t->decimal('discount_total', 15, 2)->default(0), $t->decimal('line_total', 15, 2), $t->timestamps()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sale_orders');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchase_orders');
    }
};
