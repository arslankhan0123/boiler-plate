<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tenant-DB tables for column-level visibility.
     *
     * `resource_columns` is the catalog: every column a resource can expose, with
     * its base exposure (listing/detail/hidden). `column_visibilities` is the
     * per-role override matrix — a role can raise a column's exposure (e.g. reveal
     * a column that ships as `hidden`). Both live in the tenant DB so visibility
     * policy is isolated per tenant, alongside roles/permissions.
     */
    public function up(): void
    {
        Schema::create('resource_columns', function (Blueprint $table) {
            $table->id();
            $table->string('resource');               // e.g. 'users'
            $table->string('column');                 // e.g. 'phone'
            $table->string('label')->nullable();      // optional human label
            $table->string('exposure')->default('hidden'); // listing|detail|hidden
            $table->boolean('is_sensitive')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['resource', 'column']);
        });

        Schema::create('column_visibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();
            $table->foreignId('resource_column_id')
                ->constrained('resource_columns')
                ->cascadeOnDelete();
            $table->string('exposure'); // listing|detail|hidden (role-level grant)
            $table->timestamps();

            $table->unique(['role_id', 'resource_column_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('column_visibilities');
        Schema::dropIfExists('resource_columns');
    }
};
