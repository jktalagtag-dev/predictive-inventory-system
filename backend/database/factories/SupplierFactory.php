<?php

namespace Database\Factories;

use App\Domains\Procurement\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('SUP-????')),
            'legal_name' => fake()->unique()->company(),
            'tax_identifier' => fake()->unique()->numerify('###-###-###-###'),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => null,
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country_code' => 'PH',
            'default_currency_code' => 'PHP',
            'is_active' => true,
            'row_version' => 1,
        ];
    }
}
