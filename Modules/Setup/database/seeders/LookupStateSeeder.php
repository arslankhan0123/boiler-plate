<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setup\Database\Seeders\Concerns\SeedsLookups;
use Modules\Setup\Models\Lookup;

/**
 * Seeds the CENTRAL `lookups` master with all states/provinces (type=state) from
 * `data/states.json`. Each state's `parent_id` links to its country lookup row,
 * resolved via the dr5hn country id → lookup id map. Must run AFTER countries.
 */
class LookupStateSeeder extends Seeder
{
    use SeedsLookups;

    public function run(): void
    {
        /** @var array<string, int> $countryMap  dr5hn country id => lookup id */
        $countryMap = Lookup::query()
            ->where('type', Lookup::TYPE_COUNTRY)
            ->pluck('id', 'external_id')
            ->all();

        /** @var list<array<string, string|float|null>> $rows */
        $rows = json_decode((string) file_get_contents($this->lookupDataPath('states.json')), true);

        $skipped = 0;
        $count = $this->upsertLookups($this->rows($rows, $countryMap, $skipped), [
            'parent_id', 'code', 'name', 'latitude', 'longitude', 'is_active', 'updated_at',
        ]);

        $this->command->info("Lookups: seeded {$count} states (skipped {$skipped} with no matching country).");
    }

    /**
     * @param  list<array<string, string|float|null>>  $rows
     * @param  array<string, int>  $countryMap
     * @return iterable<array<string, mixed>>
     */
    private function rows(array $rows, array $countryMap, int &$skipped): iterable
    {
        foreach ($rows as $row) {
            $parentId = $countryMap[(string) $row['country_id']] ?? null;

            if ($parentId === null) {
                $skipped++;

                continue;
            }

            yield [
                'type' => Lookup::TYPE_STATE,
                'external_id' => $row['external_id'],
                'parent_id' => $parentId,
                'code' => $row['state_code'],
                'name' => $row['name'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'is_active' => true,
                'sort_order' => 0,
            ];
        }
    }
}
