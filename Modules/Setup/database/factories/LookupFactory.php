<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setup\Models\Lookup;

/**
 * @extends Factory<Lookup>
 */
class LookupFactory extends Factory
{
    protected $model = Lookup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $iso2 = mb_strtoupper($this->faker->unique()->lexify('??'));

        return [
            'type' => Lookup::TYPE_COUNTRY,
            'code' => $iso2,
            'name' => $this->faker->unique()->country(),
            'image' => 'https://flagcdn.com/'.mb_strtolower($iso2).'.svg',
            'iso2' => $iso2,
            'iso3' => mb_strtoupper($this->faker->lexify('???')),
            'numeric_code' => (string) $this->faker->numberBetween(100, 999),
            'phone_code' => '+'.$this->faker->numberBetween(1, 999),
            'capital' => $this->faker->city(),
            'region' => $this->faker->randomElement(['Asia', 'Europe', 'Africa', 'Americas', 'Oceania']),
            'currency_code' => mb_strtoupper($this->faker->lexify('???')),
            'currency_symbol' => '$',
            'locale' => 'en_US',
            'timezone' => 'UTC',
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
