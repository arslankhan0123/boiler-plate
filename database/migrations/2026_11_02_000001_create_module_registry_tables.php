<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master (central) DB: module registry + per-tenant module assignment.
 *  modules                 registry (synced from config/modules.php)
 *  module_dependencies     module → required module
 *  tenant_modules          modules enabled for a tenant
 *  tenant_module_features  per-tenant feature (submodule) switches
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $t) {
            $t->string('code', 50)->primary();
            $t->string('name');
            $t->string('description')->nullable();
            $t->string('icon', 60)->nullable();
            $t->string('scope', 20)->default('tenant'); // platform | core | tenant
            $t->string('group', 40)->nullable();
            $t->unsignedSmallInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('module_dependencies', function (Blueprint $t) {
            $t->id();
            $t->string('module_code', 50);
            $t->string('requires_code', 50);
            $t->unique(['module_code', 'requires_code']);
            $t->foreign('module_code')->references('code')->on('modules')->cascadeOnDelete();
            $t->foreign('requires_code')->references('code')->on('modules')->cascadeOnDelete();
        });

        Schema::create('tenant_modules', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id');
            $t->string('module_code', 50);
            $t->timestamp('enabled_at')->nullable();
            $t->unique(['tenant_id', 'module_code']);
            $t->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('tenant_module_features', function (Blueprint $t) {
            $t->id();
            $t->string('tenant_id');
            $t->string('module_code', 50);
            $t->string('feature_code', 60);
            $t->boolean('is_enabled')->default(true);
            $t->unique(['tenant_id', 'module_code', 'feature_code'], 'tenant_feature_unique');
            $t->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete()->cascadeOnUpdate();
        });

        // Carry over modules previously stored in tenants.data JSON.
        $map = ['sale' => 'sales', 'accounting' => 'accounting', 'inventory' => 'inventory', 'pos' => 'pos',
            'purchase' => 'purchase', 'ecommerce' => 'ecommerce'];
        foreach (DB::table('tenants')->get(['id', 'data']) as $tenant) {
            $data = json_decode((string) $tenant->data, true) ?: [];
            foreach ((array) ($data['modules'] ?? []) as $old) {
                if (isset($map[$old])) {
                    DB::table('tenant_modules')->insertOrIgnore([
                        'tenant_id' => $tenant->id, 'module_code' => $map[$old], 'enabled_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_module_features');
        Schema::dropIfExists('tenant_modules');
        Schema::dropIfExists('module_dependencies');
        Schema::dropIfExists('modules');
    }
};
