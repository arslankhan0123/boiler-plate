<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Drops every physical tenant database (those named `{prefix}*{suffix}`, e.g.
 * tenant_001_database) directly from the server's database list — INCLUDING
 * orphans that no longer have a row in the central `tenants` table.
 *
 * `migrate:fresh` rebuilds only the central DB and leaves the per-tenant
 * databases behind; re-seeding then tries to recreate them and fails with
 * TenantDatabaseAlreadyExistsException. Run this first to reset cleanly.
 */
class DropTenantDatabases extends Command
{
    protected $signature = 'tenants:drop-databases {--force : Drop without interactive confirmation}';

    protected $description = 'Drop ALL physical tenant_* databases (incl. orphans). Run before migrate:fresh --seed to avoid TenantDatabaseAlreadyExistsException.';

    public function handle(): int
    {
        $connection = Config::string('tenancy.database.central_connection');
        $prefix = Config::string('tenancy.database.prefix');
        $suffix = Config::string('tenancy.database.suffix');

        $db = DB::connection($connection);
        $central = $db->getDatabaseName();

        // Match by the configured naming scheme against the live database list so
        // orphans (no central row) are caught. Never touch the central DB itself.
        $names = [];
        foreach ($db->select('SELECT datname FROM pg_database WHERE datistemplate = false ORDER BY datname') as $row) {
            $name = (string) $row->datname;

            if ($name !== $central && str_starts_with($name, $prefix) && str_ends_with($name, $suffix)) {
                $names[] = $name;
            }
        }

        if ($names === []) {
            $this->info('No tenant databases found — nothing to drop.');

            return self::SUCCESS;
        }

        $this->warn(count($names).' tenant database(s) will be PERMANENTLY dropped:');
        foreach ($names as $name) {
            $this->line("  - {$name}");
        }

        if (! $this->option('force') && ! $this->confirm('Drop these databases?', false)) {
            $this->info('Aborted — nothing dropped.');

            return self::SUCCESS;
        }

        foreach ($names as $name) {
            // Terminate any open sessions first, otherwise DROP DATABASE is refused.
            $db->select('SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = ?', [$name]);
            $db->statement('DROP DATABASE IF EXISTS "'.$name.'"');
            $this->info("Dropped {$name}.");
        }

        $this->info('Done — dropped '.count($names).' tenant database(s).');

        return self::SUCCESS;
    }
}
