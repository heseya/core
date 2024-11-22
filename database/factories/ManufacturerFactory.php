<?php

namespace Database\Factories;

use Domain\Manufacturer\Models\Manufacturer;

class ManufacturerFactory extends Factory
{
    /** @var string */
    protected $model = Manufacturer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
        ];
    }
}
