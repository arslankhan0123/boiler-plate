<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setup\Database\Seeders\LookupCitySeeder;
use Modules\Setup\Database\Seeders\LookupCountrySeeder;
use Modules\Setup\Database\Seeders\LookupCurrencySeeder;
use Modules\Setup\Database\Seeders\LookupStateSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the central database.
     *
     * Note: model events are intentionally NOT muted here — creating a tenant
     * relies on the TenantCreated event to provision and migrate its database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            // Central master reference data (shared by all tenants). Order matters:
            // states link to countries and cities link to states via parent_id, so
            // parents must be seeded first.
            LookupCountrySeeder::class,
            LookupCurrencySeeder::class,
            LookupStateSeeder::class,
            LookupCitySeeder::class,
            TenantSeeder::class,
        ]);
    }
}
