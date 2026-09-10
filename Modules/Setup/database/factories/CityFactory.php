<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setup\Models\City;
use Modules\Setup\Models\State;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lookup_id' => $this->faker->unique()->numberBetween(1, 10000000),
            'external_id' => (string) $this->faker->unique()->numberBetween(1, 10000000),
            'name' => $this->faker->city(),
            'state_id' => State::factory(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
            'created_by' => null,
        ];
    }
}
