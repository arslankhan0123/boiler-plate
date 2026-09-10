<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setup\Models\Currency;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lookup_id' => $this->faker->unique()->numberBetween(1, 100000),
            'code' => mb_strtoupper($this->faker->unique()->lexify('???')),
            'name' => $this->faker->word().' Dollar',
            'symbol' => '$',
            'numeric_code' => (string) $this->faker->numberBetween(100, 999),
            'decimal_digits' => 2,
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
            'created_by' => null,
        ];
    }
}
