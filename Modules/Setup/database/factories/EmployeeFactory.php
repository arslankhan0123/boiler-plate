<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setup\Models\Department;
use Modules\Setup\Models\Designation;
use Modules\Setup\Models\Employee;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'code' => mb_strtoupper($this->faker->unique()->bothify('EMP-####')),
            'department_id' => Department::factory(),
            'designation_id' => Designation::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->numerify('+1-###-###-####'),
            'dob' => $this->faker->date(),
            'gender' => $this->faker->randomElement(['male', 'female', 'others']),
            'marital_status' => $this->faker->randomElement(['married', 'unmarried', 'divorced']),
            'blood_group' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'religion' => null,
            'country_id' => null,
            'state_id' => null,
            'street' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'zip' => $this->faker->postcode(),
            'national_id' => $this->faker->numerify('NID#######'),
            'iqama_no' => $this->faker->numerify('IQ#######'),
            'iqama_no_expiry_date' => $this->faker->date(),
            'passport' => null,
            'passport_expiry_date' => null,
            'driving_license_no' => null,
            'driving_license_expiry_date' => null,
            'tuv_no' => null,
            'tuv_no_expiry_date' => null,
            'join_date' => $this->faker->date(),
            'type' => null,
            'employment_type' => $this->faker->randomElement(['permanent', 'temporary', 'contract']),
            'duty_type' => $this->faker->randomElement(['full-time', 'part-time']),
            'bank_name' => null,
            'bank_branch_name' => null,
            'bank_account_no' => null,
            'iban_num' => null,
            'company_name' => null,
            'basic_salary' => $this->faker->randomFloat(2, 1000, 9000),
            'transport_allowance' => null,
            'gross_salary' => null,
            'hourly_rate' => null,
            'data' => null,
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
            'created_by' => null,
        ];
    }
}
