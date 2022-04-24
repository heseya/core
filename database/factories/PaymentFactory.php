<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'external_id' => $this->faker->uuid,
            'method' => $this->faker->randomElement(['przelewy24', 'bluemedia', 'paynow']),
            'paid' => $this->faker->boolean,
            'amount' => rand(10, 1000),
            'redirect_url' => 'https://heseya.com/pay',
            'continue_url' => 'https://store.heseya.com/done',
        ];
    }
}
