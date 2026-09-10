<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Seeders\Concerns;

use Illuminate\Support\Facades\Date;
use Modules\Setup\Models\Lookup;

/**
 * Shared helpers for the central `lookups` seeders: locate the bundled data files
 * and bulk-upsert rows in chunks, keyed on the canonical (type, external_id).
 *
 * Bulk upsert does NOT auto-fill timestamps, so created_at/updated_at are stamped
 * here; only updated_at is in the update set (created_at must not bump on re-seed).
 * Accepts a generator so huge datasets (cities) stream without materialising.
 */
trait SeedsLookups
{
    protected function lookupDataPath(string $file): string
    {
        return base_path('Modules/Setup/database/data/'.$file);
    }

    /**
     * @param  iterable<array<string, mixed>>  $rows  each row must include 'type' and 'external_id'
     * @param  list<string>  $update  columns to overwrite when the row already exists
     */
    protected function upsertLookups(iterable $rows, array $update, int $chunkSize = 1000): int
    {
        $now = Date::now()->toDateTimeString();
        $buffer = [];
        $total = 0;

        foreach ($rows as $row) {
            $row['created_at'] ??= $now;
            $row['updated_at'] = $now;
            $buffer[] = $row;
            $total++;

            if (count($buffer) >= $chunkSize) {
                Lookup::query()->upsert($buffer, ['type', 'external_id'], $update);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            Lookup::query()->upsert($buffer, ['type', 'external_id'], $update);
        }

        return $total;
    }
}
