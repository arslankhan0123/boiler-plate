<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retail: sales / purchase documents (one engine for invoices, quotations, delivery notes,
 * returns, purchase orders, purchases and POS holds), payments, coupons, salesmen, POS void log.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Retail fields on the shared customer list.
        Schema::table('customers', function (Blueprint $t) {
            $t->decimal('credit_limit', 15, 2)->default(0)->after('opening_balance');
            $t->string('price_level_type', 10)->nullable()->after('credit_limit'); // increase | decrease
            $t->decimal('price_level', 9, 2)->default(0)->after('price_level_type'); // % applied to sales price
            $t->string('contact_person')->nullable()->after('company_name');
            $t->string('ship_address')->nullable();
            $t->string('ship_city')->nullable();
            $t->string('ship_zip', 32)->nullable();
        });

        Schema::create('rt_salesmen', function (Blueprint $t) {
            $t->id();
            $t->string('code', 30)->nullable();
            $t->string('name');
            $t->string('mobile', 50)->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->decimal('opening_balance', 15, 2)->default(0); // amount already owed to the salesman
            $t->decimal('commission_rate', 9, 2)->default(0);  // optional % of sales, on top of seller points
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });

        Schema::create('rt_coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->string('type', 10)->default('percentage'); // percentage | fixed
            $t->decimal('value', 15, 2);
            $t->decimal('min_amount', 15, 2)->default(0);
            $t->date('expire_date')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        // A coupon issued to one customer, with its own code; single use.
        Schema::create('rt_customer_coupons', function (Blueprint $t) {
            $t->id();
            $t->foreignId('coupon_id')->constrained('rt_coupons')->cascadeOnDelete();
            $t->unsignedBigInteger('customer_id')->index();
            $t->string('code', 50)->unique();
            $t->date('expire_date')->nullable();
            $t->unsignedBigInteger('used_document_id')->nullable();
            $t->timestamp('used_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('rt_documents', function (Blueprint $t) {
            $t->id();
            $t->string('type', 20)->index(); // invoice, quotation, delivery, sale_return, order, purchase, purchase_return, hold
            $t->string('number', 50);
            $t->string('reference_no', 100)->nullable();
            $t->date('date');
            $t->date('due_date')->nullable(); // due date / quotation expiry / expected delivery
            $t->string('status', 20)->default('final'); // final|draft (invoice), received|pending|ordered (purchase), open|converted (quotation…)
            $t->unsignedBigInteger('customer_id')->nullable()->index();
            $t->unsignedBigInteger('supplier_id')->nullable()->index();
            $t->foreignId('warehouse_id')->constrained('inv_warehouses');
            $t->foreignId('salesman_id')->nullable()->constrained('rt_salesmen')->nullOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('rt_documents')->nullOnDelete(); // invoice ← quotation/DN, return ← invoice
            $t->decimal('subtotal', 15, 4)->default(0);
            $t->decimal('tax_total', 15, 4)->default(0);
            $t->decimal('item_discount_total', 15, 4)->default(0);
            $t->decimal('other_charges_input', 15, 4)->default(0);
            $t->unsignedBigInteger('other_charges_tax_id')->nullable();
            $t->decimal('other_charges_amt', 15, 4)->default(0);
            $t->decimal('discount_input', 15, 4)->default(0);
            $t->string('discount_type', 10)->default('percentage');
            $t->decimal('discount_amt', 15, 4)->default(0);
            $t->unsignedBigInteger('coupon_id')->nullable();
            $t->unsignedBigInteger('customer_coupon_id')->nullable();
            $t->string('coupon_code', 50)->nullable();
            $t->decimal('coupon_amt', 15, 4)->default(0);
            $t->decimal('round_off', 15, 4)->default(0);
            $t->decimal('grand_total', 15, 4)->default(0);
            $t->decimal('paid_amount', 15, 4)->default(0);
            $t->string('payment_status', 10)->default('unpaid'); // unpaid | partial | paid
            $t->decimal('previous_due', 15, 4)->default(0); // customer due before this invoice (printed)
            $t->boolean('is_pos')->default(false);
            $t->boolean('update_prices')->default(false); // purchase: push new cost / sales price to items
            $t->text('note')->nullable();
            $t->text('terms')->nullable();
            $t->json('meta')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['type', 'number']);
            $t->index(['type', 'date']);
        });

        Schema::create('rt_document_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained('rt_documents')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained('inv_items');
            $t->string('description')->nullable();
            $t->decimal('qty', 15, 3);
            $t->decimal('unit_price', 15, 4);
            $t->unsignedBigInteger('tax_id')->nullable();
            $t->decimal('tax_rate', 9, 4)->default(0);
            $t->string('tax_type', 10)->default('exclusive');
            $t->decimal('tax_amt', 15, 4)->default(0);
            $t->string('discount_type', 10)->default('percentage');
            $t->decimal('discount_input', 15, 4)->default(0);
            $t->decimal('discount_amt', 15, 4)->default(0);
            $t->decimal('unit_total', 15, 4)->default(0);
            $t->decimal('total', 15, 4)->default(0);
            $t->decimal('purchase_price', 15, 4)->default(0); // cost at the time (profit reports)
            $t->decimal('profit_margin', 9, 4)->nullable();   // purchase: new margin
            $t->decimal('sales_price', 15, 4)->nullable();    // purchase: new sales price
            $t->decimal('seller_points', 15, 4)->default(0);
            $t->timestamps();
        });

        Schema::create('rt_payments', function (Blueprint $t) {
            $t->id();
            $t->string('code', 50)->index();
            $t->string('direction', 3); // in (money received) | out (money paid)
            $t->string('kind', 10)->default('payment'); // payment | opening | advance | refund
            $t->string('party_type', 10); // customer | supplier
            $t->unsignedBigInteger('party_id')->index();
            $t->foreignId('document_id')->nullable()->constrained('rt_documents')->cascadeOnDelete();
            $t->date('date');
            $t->string('method', 50)->default('Cash');
            $t->decimal('amount', 15, 4);
            $t->decimal('change_return', 15, 4)->default(0);
            $t->string('cheque_no', 50)->nullable();
            $t->date('cheque_date')->nullable();
            $t->string('cheque_status', 10)->nullable(); // pending | cleared | bounced
            $t->text('note')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
            $t->index(['party_type', 'party_id', 'kind']);
        });

        Schema::create('rt_salesman_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('salesman_id')->constrained('rt_salesmen')->cascadeOnDelete();
            $t->string('kind', 10)->default('payment'); // payment | advance
            $t->date('date');
            $t->decimal('amount', 15, 2);
            $t->string('method', 50)->default('Cash');
            $t->text('note')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });

        // Items removed from a POS cart before checkout.
        Schema::create('rt_void_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('item_id')->nullable();
            $t->string('item_name');
            $t->decimal('qty', 15, 3);
            $t->decimal('price', 15, 4);
            $t->unsignedBigInteger('customer_id')->nullable();
            $t->string('reason')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['rt_void_logs', 'rt_salesman_payments', 'rt_payments', 'rt_document_items', 'rt_documents', 'rt_customer_coupons', 'rt_coupons', 'rt_salesmen'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('customers', fn (Blueprint $t) => $t->dropColumn(['credit_limit', 'price_level_type', 'price_level', 'contact_person', 'ship_address', 'ship_city', 'ship_zip']));
    }
};
