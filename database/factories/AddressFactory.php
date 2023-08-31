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
            'name' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->streetAddress,
            'zip' => $this->faker->postcode,
            'city' => $this->faker->city,
            'country' => $this->faker->countryCode,
        ];
    }
}
