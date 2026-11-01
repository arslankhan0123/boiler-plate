<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

/**
 * php artisan db:seed --class=MasterAdminSeeder
 *
 * Creates every module permission, a "Master Admin" role holding all of them,
 * and a Master Admin user (master@admin.com / password).
 */
class MasterAdminSeeder extends Seeder
{
    private const GUARD = 'api';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = [];
        foreach (array_keys(config('module_permissions.modules')) as $module) {
            foreach (config('module_permissions.actions') as $action) {
                $names[] = "{$module}.{$action}";
                Permission::findOrCreate("{$module}.{$action}", self::GUARD);
            }
        }

        $role = Role::findOrCreate(config('module_permissions.master_role'), self::GUARD);
        $role->syncPermissions($names);

        $user = User::query()->updateOrCreate(
            ['email' => 'master@admin.com'],
            [
                'name' => 'Master Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => null,
            ],
        );
        $user->syncRoles([$role]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Master Admin ready: master@admin.com / password');
    }
}
