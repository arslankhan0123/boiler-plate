<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setup\Models\Department;
use Modules\Setup\Models\Designation;

/**
 * @extends Factory<Designation>
 */
class DesignationFactory extends Factory
{
    protected $model = Designation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->jobTitle(),
            'code' => mb_strtoupper($this->faker->unique()->lexify('DSG-???')),
            'description' => $this->faker->sentence(),
            'department_id' => Department::factory(),
            'data' => null,
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
            'created_by' => null,
        ];
    }
}
