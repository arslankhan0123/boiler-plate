<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setup\Database\Seeders\Concerns\SeedsLookups;
use Modules\Setup\Models\Lookup;

/**
 * Seeds the CENTRAL `lookups` master with the complete ISO-3166-1 country set
 * (type=country) from the bundled `data/countries.json`. Idempotent (upsert keyed
 * on type+external_id). Tenants pick from this list; their own `countries` table
 * is NOT seeded.
 *
 * `external_id` is the dr5hn country id (shared id-space with states/cities so
 * those resolve their parent). `locale`/`timezone` are curated best-effort values.
 */
class LookupCountrySeeder extends Seeder
{
    use SeedsLookups;

    public function run(): void
    {
        /** @var list<array<string, string|null>> $rows */
        $rows = json_decode((string) file_get_contents($this->lookupDataPath('countries.json')), true);

        $count = $this->upsertLookups($this->rows($rows), [
            'parent_id', 'code', 'name', 'image', 'iso2', 'iso3', 'numeric_code', 'phone_code',
            'capital', 'region', 'currency_code', 'currency_symbol', 'locale', 'timezone',
            'is_active', 'updated_at',
        ]);

        $this->command->info("Lookups: seeded {$count} countries.");
    }

    /**
     * @param  list<array<string, string|null>>  $rows
     * @return iterable<array<string, mixed>>
     */
    private function rows(array $rows): iterable
    {
        foreach ($rows as $row) {
            $iso2 = (string) $row['iso2'];

            yield [
                'type' => Lookup::TYPE_COUNTRY,
                'external_id' => $row['external_id'],
                'parent_id' => null,
                'code' => $iso2,
                'name' => $row['name'],
                'image' => 'https://flagcdn.com/'.mb_strtolower($iso2).'.svg',
                'iso2' => $iso2,
                'iso3' => $row['iso3'],
                'numeric_code' => $row['numeric_code'],
                'phone_code' => $row['phone_code'],
                'capital' => $row['capital'],
                'region' => $row['region'],
                'currency_code' => $row['currency_code'],
                'currency_symbol' => $row['currency_symbol'],
                'locale' => $row['locale'],
                'timezone' => $row['timezone'],
                'is_active' => true,
                'sort_order' => 0,
            ];
        }
    }
}
