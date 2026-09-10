<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    /**
     * The tenants to seed. Each entry creates a tenant (which provisions and
     * migrates its own database via the TenantCreated job pipeline) plus a
     * tenant-scoped admin user in the central identity directory.
     *
     * @var list<array{name: string, admin_name: string, admin_email: string, admin_phone: string}>
     */
    private const TENANTS = [
        [
            'name' => 'Boiler Plate Demo Store',
            'admin_name' => 'Demo Store Tenant Admin',
            'admin_email' => 'admin@demo.test',
            'admin_phone' => '+10000000001',
        ],
        [
            'name' => 'Acme Retailers',
            'admin_name' => 'Acme Tenant Admin',
            'admin_email' => 'admin@acme.test',
            'admin_phone' => '+10000000002',
        ],
        [
            'name' => 'Globex Mart',
            'admin_name' => 'Globex Tenant Admin',
            'admin_email' => 'admin@globex.test',
            'admin_phone' => '+10000000003',
        ],
    ];

    /**
     * Seed every configured tenant. Idempotent: keyed on name/email.
     */
    public function run(): void
    {
        foreach (self::TENANTS as $config) {
            $this->seedTenant($config);
        }
    }

    /**
     * @param  array{name: string, admin_name: string, admin_email: string, admin_phone: string}  $config
     */
    private function seedTenant(array $config): void
    {
        /** @var Tenant $tenant */
        $tenant = Tenant::query()->firstOrCreate(
            ['name' => $config['name']],
            ['is_active' => true],
        );

        $user = User::query()->updateOrCreate(
            ['email' => $config['admin_email']],
            [
                'name' => $config['admin_name'],
                'phone' => $config['admin_phone'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'tenant_id' => $tenant->id,
                'is_platform_admin' => false,
            ],
        );

        // Assign the tenant-scoped role inside the tenant DB (where the role and
        // the model_has_roles pivot live). The roles were seeded during tenant
        // provisioning via the SeedDatabase job (TenantDatabaseSeeder).
        $tenant->run(function () use ($user): void {
            $user->assignRole('Tenant Admin');
        });
    }
}
