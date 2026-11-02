<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Compound/combined taxes: a group row whose rate = sum of its component tax rates. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_rates', function (Blueprint $t) {
            $t->boolean('is_group')->default(false)->after('type');
            $t->json('components')->nullable()->after('is_group'); // [tax_rate_id, ...]
        });
    }

    public function down(): void
    {
        Schema::table('tax_rates', fn (Blueprint $t) => $t->dropColumn(['is_group', 'components']));
    }
};
