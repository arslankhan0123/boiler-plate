<?php

declare(strict_types=1);

namespace Modules\Setup\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Setup\Models\Supplier;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company(),
            'vat_number' => $this->faker->numerify('VAT#########'),
            'contact_person' => $this->faker->name(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->numerify('+1-###-###-####'),
            'whatsapp' => null,
            'website' => $this->faker->url(),
            'group_name' => $this->faker->randomElement(['Wholesale', 'Retail', 'Distributor', null]),
            'currency_id' => null,
            'country_id' => null,
            'state_id' => null,
            'default_language' => 'en',
            'street' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'zip' => $this->faker->postcode(),
            'po_box' => null,
            'mailing_address' => null,
            'opening_balance' => 0,
            'data' => null,
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 0,
            'created_by' => null,
        ];
    }
}
