<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Deletes ONLY the files that belong to one tenant. Must run while the
 * tenant database still exists (we read the stored paths from it).
 */
class TenantMediaCleaner
{
    public function clean(Tenant $tenant): int
    {
        $own = $this->folderFor($tenant);
        $paths = collect($this->collectPaths($tenant))
            ->map(fn ($p) => ltrim(Str::after((string) $p, '/storage/'), '/'))
            ->reject(fn ($p) => $p === '' || Str::startsWith($p, ['http://', 'https://', '//', 'data:']) || str_contains($p, '..') || ! str_contains($p, '/'))
            ->unique()->values()->all();
        $deleted = 0;

        foreach (config('tenant_media.disks', []) as $disk) {
            $storage = Storage::disk($disk);

            foreach ($paths as $path) {
                // Never touch another tenant's folder.
                if (Str::startsWith($path, 'tenants/') && ! Str::startsWith($path, $own.'/')) {
                    continue;
                }
                if ($storage->exists($path) && $storage->delete($path)) {
                    $deleted++;
                }
            }

            // The tenant's own upload folder.
            if ($storage->directoryExists($own)) {
                $storage->deleteDirectory($own);
            }
        }

        Log::info("Tenant {$tenant->getTenantKey()} media cleaned", ['files' => $deleted, 'folder' => $own]);

        return $deleted;
    }

    public function folderFor(Tenant $tenant): string
    {
        return str_replace('{id}', (string) $tenant->getTenantKey(), config('tenant_media.folder', 'tenants/{id}'));
    }

    /** File paths stored in the tenant's own database (relative paths only, no URLs). */
    private function collectPaths(Tenant $tenant): array
    {
        try {
            return $tenant->run(function (): array {
                $paths = [];
                foreach (config('tenant_media.columns', []) as $table => $columns) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }
                    foreach ($columns as $column) {
                        if (! Schema::hasColumn($table, $column)) {
                            continue;
                        }
                        DB::table($table)->whereNotNull($column)->where($column, '!=', '')
                            ->select($column)->orderBy($column)
                            ->chunk(500, function ($rows) use (&$paths, $column) {
                                foreach ($rows as $row) {
                                    $paths[] = $row->{$column};
                                }
                            });
                    }
                }

                return $paths;
            });
        } catch (Throwable $e) {
            report($e); // DB missing/unreachable → still remove the tenant folder

            return [];
        }
    }
}
