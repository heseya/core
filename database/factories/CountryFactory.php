<?php

namespace Database\Factories;

use App\Models\Country;

class CountryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Country::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->countryCode,
            'name' => $this->faker->country,
        ];
    }
}
