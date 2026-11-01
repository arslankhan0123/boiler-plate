<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The specific person an employee reports to (org chart by people, not only designation). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $t) {
            if (! Schema::hasColumn('employees', 'manager_id')) {
                $t->unsignedBigInteger('manager_id')->nullable()->after('designation_id')->index(); // employees.id
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', fn (Blueprint $t) => $t->dropColumn('manager_id'));
    }
};
