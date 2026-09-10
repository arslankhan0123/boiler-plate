<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setup\Models\Area;
use Modules\Setup\Models\City;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    protected $model = Area::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'city_id' => City::factory(),
            'name' => $this->faker->streetName(),
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => null,
            'area_code' => $this->faker->postcode(),
            'phone' => $this->faker->numerify('+1-###-###-####'),
            'email' => $this->faker->safeEmail(),
            'description' => $this->faker->sentence(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'data' => null,
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
            'created_by' => null,
        ];
    }
}
