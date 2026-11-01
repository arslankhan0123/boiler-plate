<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tenant settings: key/value store, branches, tax rates, payment & shipping methods. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('code', 30)->nullable();
            $t->string('phone', 40)->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->string('city', 100)->nullable();
            $t->unsignedBigInteger('manager_id')->nullable(); // employees.id
            $t->boolean('is_head_office')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('tax_rates', function (Blueprint $t) {
            $t->id();
            $t->string('name');                 // e.g. GST, Sales Tax, WHT
            $t->decimal('rate', 6, 3);          // percent
            $t->string('type', 12)->default('exclusive'); // exclusive|inclusive
            $t->boolean('is_default')->default(false);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('type', 20)->default('cash'); // cash|bank|card|wallet|cod|online
            $t->text('details')->nullable();         // account no., IBAN, instructions
            $t->boolean('is_active')->default(true);
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('shipping_methods', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->decimal('fee', 12, 2)->default(0);
            $t->decimal('free_over', 12, 2)->nullable();  // free shipping above this order total
            $t->string('delivery_time', 60)->nullable();  // "2-4 days"
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['shipping_methods', 'payment_methods', 'tax_rates', 'branches', 'tenant_settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
