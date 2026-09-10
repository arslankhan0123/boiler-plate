<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setup\Models\Country;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $iso2 = mb_strtoupper($this->faker->unique()->lexify('??'));

        return [
            'lookup_id' => $this->faker->unique()->numberBetween(1, 100000),
            'name' => $this->faker->unique()->country(),
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
            'flag_image' => 'https://flagcdn.com/'.mb_strtolower($iso2).'.svg',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
            'created_by' => null,
        ];
    }
}
