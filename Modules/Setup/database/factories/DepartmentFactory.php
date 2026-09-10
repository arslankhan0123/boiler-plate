<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setup\Models\Department;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(['Sales', 'Purchasing', 'Warehouse', 'Finance', 'HR', 'IT', 'Operations']),
            'code' => mb_strtoupper($this->faker->unique()->lexify('???')),
            'description' => $this->faker->sentence(),
            'data' => null,
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
            'created_by' => null,
        ];
    }
}
