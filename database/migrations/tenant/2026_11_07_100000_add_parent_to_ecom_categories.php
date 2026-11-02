<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Store categories can nest: a category may have a parent category (subcategories). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecom_categories', function (Blueprint $t) {
            if (! Schema::hasColumn('ecom_categories', 'parent_id')) {
                $t->unsignedBigInteger('parent_id')->nullable()->after('id')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ecom_categories', function (Blueprint $t) {
            if (Schema::hasColumn('ecom_categories', 'parent_id')) {
                $t->dropColumn('parent_id');
            }
        });
    }
};
