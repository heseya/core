<?php

namespace Database\Factories;

use App\Enums\SavedAddressType;
use App\Models\Address;
use App\Models\SavedAddress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavedAddressFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SavedAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'default' => $this->faker->boolean,
            'name' => $this->faker->word,
            'address_id' => Address::all()->random()->id,
            'user_id' => User::all()->random()->id,
            'type' => rand(0, 1),
        ];
    }
}
