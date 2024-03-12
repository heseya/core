<?php

namespace Database\Factories;

use App\Models\Deposit;

class DepositFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Deposit::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'quantity' => mt_rand(1, 20),
            'shipping_time' => $this->faker->numberBetween(1, 7),
        ];
    }
}
