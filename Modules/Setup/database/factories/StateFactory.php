<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setup\Models\Country;
use Modules\Setup\Models\State;

/**
 * @extends Factory<State>
 */
class StateFactory extends Factory
{
    protected $model = State::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lookup_id' => $this->faker->unique()->numberBetween(1, 1000000),
            'external_id' => (string) $this->faker->unique()->numberBetween(1, 1000000),
            'name' => $this->faker->randomElement(['California', 'Texas', 'Bavaria', 'Ontario', 'Maharashtra', 'Queensland']),
            'code' => mb_strtoupper($this->faker->lexify('??')),
            'country_id' => Country::factory(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
            'created_by' => null,
        ];
    }
}
