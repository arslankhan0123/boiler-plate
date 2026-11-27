<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ModuleAccess;
use App\Support\ModuleRegistry;
use Illuminate\Console\Command;

/**
 * Why can't a user see a screen? Prints, per module feature: feature enabled for the tenant,
 * permission exists in the tenant DB, and whether the user has it.
 *
 *   php artisan access:check gilaf@mailinator.com purchase
 */
class CheckAccess extends Command
{
    protected $signature = 'access:check {email} {module?}';

    protected $description = 'Show which module features a user can / cannot open, and why';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->firstOrFail();
        $tenant = $user->tenant;
        $run = function () use ($user) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $this->info('Roles: '.$user->getRoleNames()->implode(', '));
            $rows = [];
            foreach (ModuleRegistry::all() as $code => $m) {
                if ($this->argument('module') && $this->argument('module') !== $code) {
                    continue;
                }
                foreach ($m['features'] as $key => $f) {
                    $perm = $f['permission'] ?? '-';
                    $exists = \Spatie\Permission\Models\Permission::query()->where('name', $perm)->exists();
                    $rows[] = [$code, $key, ModuleAccess::moduleAvailable($user, $code) ? 'yes' : 'NO', ModuleAccess::featureAvailable($user, $code, $key) ? 'yes' : 'NO',
                        $perm, $exists ? 'yes' : 'MISSING', $user->can($perm) ? 'yes' : 'NO', $f['route'] ?? '-'];
                }
            }
            $this->table(['Module', 'Feature', 'Module on', 'Feature on', 'Permission', 'Perm in DB', 'User has', 'Route'], $rows);
        };
        $tenant ? $tenant->run($run) : $run();

        return self::SUCCESS;
    }
}
