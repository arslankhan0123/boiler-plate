<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** E-commerce: catalog, categories, orders, shipping, coupons, reviews, carts. Builds on Inventory + Sales. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecom_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->unsignedInteger('sort')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('ecom_products', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('item_id')->index();        // inv item this listing publishes
            $t->unsignedBigInteger('category_id')->nullable()->index();
            $t->string('name');
            $t->string('slug')->unique();
            $t->text('description')->nullable();
            $t->decimal('price', 14, 2)->default(0);           // store price (overrides item sales_price)
            $t->decimal('compare_at_price', 14, 2)->nullable(); // "was" price for a strike-through
            $t->string('image_url')->nullable();
            $t->boolean('is_published')->default(false);
            $t->boolean('is_featured')->default(false);
            $t->unsignedInteger('sort')->default(0);
            $t->string('seo_title')->nullable();
            $t->string('seo_description')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });

        Schema::create('ecom_shipping_methods', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->decimal('rate', 10, 2)->default(0);
            $t->decimal('free_over', 14, 2)->nullable();        // free shipping over this order value
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('ecom_coupons', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('type', 10)->default('percent');         // percent|fixed
            $t->decimal('value', 12, 2)->default(0);
            $t->decimal('min_order', 14, 2)->default(0);
            $t->date('starts_at')->nullable();
            $t->date('ends_at')->nullable();
            $t->unsignedInteger('usage_limit')->nullable();
            $t->unsignedInteger('used')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('ecom_orders', function (Blueprint $t) {
            $t->id();
            $t->string('order_no')->unique();
            $t->unsignedBigInteger('customer_id')->nullable()->index(); // retail customer, if matched
            $t->string('customer_name');
            $t->string('customer_email')->nullable();
            $t->string('customer_phone', 40)->nullable();
            $t->text('shipping_address')->nullable();
            $t->string('status', 16)->default('pending');       // pending|confirmed|processing|shipped|delivered|cancelled
            $t->string('payment_status', 12)->default('unpaid'); // unpaid|paid|refunded
            $t->string('payment_method', 40)->nullable();
            $t->unsignedBigInteger('shipping_method_id')->nullable();
            $t->decimal('subtotal', 14, 2)->default(0);
            $t->decimal('shipping_cost', 12, 2)->default(0);
            $t->decimal('discount', 14, 2)->default(0);
            $t->decimal('tax', 14, 2)->default(0);
            $t->decimal('grand_total', 14, 2)->default(0);
            $t->string('coupon_code')->nullable();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('sale_document_id')->nullable(); // retail Document once converted
            $t->timestamp('placed_at')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamps();
        });

        Schema::create('ecom_order_items', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('order_id')->index();
            $t->unsignedBigInteger('item_id')->nullable();
            $t->unsignedBigInteger('product_id')->nullable();
            $t->string('name');
            $t->decimal('qty', 12, 2)->default(1);
            $t->decimal('price', 14, 2)->default(0);
            $t->decimal('tax', 14, 2)->default(0);
            $t->decimal('line_total', 14, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('ecom_reviews', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('product_id')->index();
            $t->string('customer_name');
            $t->string('email')->nullable();
            $t->unsignedTinyInteger('rating')->default(5);
            $t->string('title')->nullable();
            $t->text('body')->nullable();
            $t->boolean('is_approved')->default(false);
            $t->timestamps();
        });

        Schema::create('ecom_carts', function (Blueprint $t) {
            $t->id();
            $t->string('token')->unique();
            $t->string('customer_email')->nullable();
            $t->json('items')->nullable();
            $t->decimal('subtotal', 14, 2)->default(0);
            $t->boolean('placed')->default(false);    // true once converted to an order
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['ecom_carts', 'ecom_reviews', 'ecom_order_items', 'ecom_orders', 'ecom_coupons', 'ecom_shipping_methods', 'ecom_products', 'ecom_categories'] as $tbl) {
            Schema::dropIfExists($tbl);
        }
    }
};
