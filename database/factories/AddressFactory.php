<?php

namespace Database\Factories;

use App\Models\Address;

class AddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Address::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->firstName . ' ' . $this->faker->lastName,
            'company_name' => mt_rand(0, 1) === 1 ? $this->faker->company : null,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->streetAddress,
            'zip' => $this->faker->postcode,
            'city' => $this->faker->city,
            'country' => $this->faker->countryCode,
        ];
    }
}
