<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Setup\Database\Seeders\Concerns\SeedsLookups;
use Modules\Setup\Models\Lookup;

/**
 * Seeds the CENTRAL `lookups` master with the ISO-4217 currency set
 * (type=currency) from the bundled `data/currencies.json`. Flat (no parent).
 * `external_id`=`code`=`currency_code`=alpha code; `name`=full name;
 * `currency_symbol`=symbol; `numeric_code`/`decimal_digits` best-effort.
 */
class LookupCurrencySeeder extends Seeder
{
    use SeedsLookups;

    public function run(): void
    {
        /** @var list<array<string, string|int|null>> $rows */
        $rows = json_decode((string) file_get_contents($this->lookupDataPath('currencies.json')), true);

        $count = $this->upsertLookups($this->rows($rows), [
            'parent_id', 'code', 'name', 'currency_code', 'currency_symbol',
            'numeric_code', 'decimal_digits', 'is_active', 'updated_at',
        ]);

        $this->command->info("Lookups: seeded {$count} currencies.");
    }

    /**
     * @param  list<array<string, string|int|null>>  $rows
     * @return iterable<array<string, mixed>>
     */
    private function rows(array $rows): iterable
    {
        foreach ($rows as $row) {
            $code = (string) $row['code'];

            yield [
                'type' => Lookup::TYPE_CURRENCY,
                'external_id' => $code,
                'parent_id' => null,
                'code' => $code,
                'name' => $row['name'],
                'currency_code' => $code,
                'currency_symbol' => $row['symbol'],
                'numeric_code' => $row['numeric_code'],
                'decimal_digits' => $row['decimal_digits'],
                'is_active' => true,
                'sort_order' => 0,
            ];
        }
    }
}
