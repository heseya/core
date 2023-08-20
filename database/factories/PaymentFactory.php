<?php

namespace Database\Factories;

use App\Models\Payment;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Payment>
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
            'amount' => Money::of(mt_rand(10, 1000),  Currency::DEFAULT->value),
            'redirect_url' => 'https://heseya.com/pay',
            'continue_url' => 'https://store.heseya.com/done',
            'status' => $this->faker->randomElement(['pending', 'failed', 'successful']),
        ];
    }
}
