<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Setup\Database\Seeders\Concerns\SeedsLookups;
use Modules\Setup\Models\Lookup;

/**
 * Seeds the CENTRAL `lookups` master with all cities (type=city, ~150k) from the
 * gzipped NDJSON `data/cities.ndjson.gz`. Each city's `parent_id` links to its
 * state lookup row. Must run AFTER states.
 *
 * Memory-critical: the file is streamed line-by-line (never fully decoded) and
 * upserted in chunks, so peak memory stays flat regardless of total row count.
 */
class LookupCitySeeder extends Seeder
{
    use SeedsLookups;

    public function run(): void
    {
        /** @var array<string, int> $stateMap  dr5hn state id => lookup id */
        $stateMap = Lookup::query()
            ->where('type', Lookup::TYPE_STATE)
            ->pluck('id', 'external_id')
            ->all();

        // ~150k upserts — keep the central query log from growing unbounded.
        DB::connection((new Lookup)->getConnectionName())->disableQueryLog();

        $skipped = 0;
        $count = $this->upsertLookups($this->rows($stateMap, $skipped), [
            'parent_id', 'name', 'latitude', 'longitude', 'is_active', 'updated_at',
        ]);

        $this->command->info("Lookups: seeded {$count} cities (skipped {$skipped} with no matching state).");
    }

    /**
     * Stream the gzipped NDJSON line-by-line.
     *
     * @param  array<string, int>  $stateMap
     * @return iterable<array<string, mixed>>
     */
    private function rows(array $stateMap, int &$skipped): iterable
    {
        $handle = gzopen($this->lookupDataPath('cities.ndjson.gz'), 'rb');

        if ($handle === false) {
            return;
        }

        while (($line = gzgets($handle)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            /** @var array{id: string, name: string, state_id: string, latitude: float|null, longitude: float|null} $row */
            $row = json_decode($line, true);
            $parentId = $stateMap[$row['state_id']] ?? null;

            if ($parentId === null) {
                $skipped++;

                continue;
            }

            yield [
                'type' => Lookup::TYPE_CITY,
                'external_id' => $row['id'],
                'parent_id' => $parentId,
                'code' => null,
                'name' => $row['name'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'is_active' => true,
                'sort_order' => 0,
            ];
        }

        gzclose($handle);
    }
}
