<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setup\Models\Employee;
use Modules\Setup\Models\EmployeeDocument;

/**
 * @extends Factory<EmployeeDocument>
 */
class EmployeeDocumentFactory extends Factory
{
    protected $model = EmployeeDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'name' => $this->faker->randomElement(['Passport Copy', 'Iqama Copy', 'Contract', 'Certificate']),
            'file' => null,
            'expiry_date' => $this->faker->date(),
            'created_by' => null,
        ];
    }
}
