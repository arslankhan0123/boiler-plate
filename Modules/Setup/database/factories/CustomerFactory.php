<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setup\Models\Customer;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company(),
            'code' => $this->faker->unique()->numerify('CUST-####'),
            'vat_number' => $this->faker->numerify('VAT#########'),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->numerify('+1-###-###-####'),
            'mobile' => null,
            'fax' => null,
            'whatsapp' => null,
            'website' => $this->faker->url(),
            'short_name' => null,
            'vendor_code' => null,
            'group_name' => $this->faker->randomElement(['Key Accounts', 'Walk-in', 'Online', null]),
            'currency_id' => null,
            'country_id' => null,
            'state_id' => null,
            'default_language' => 'en',
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'zip' => $this->faker->postcode(),
            'location_url' => null,
            'opening_balance' => 0,
            'data' => null,
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
            'created_by' => null,
        ];
    }
}
