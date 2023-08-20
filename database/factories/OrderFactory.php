<?php

namespace Database\Factories;

use App\Models\Order;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
//        $currency = Currency::getRandomValue();
        $currency = Currency::DEFAULT->value;
        $shipping_price = Money::of(mt_rand(8, 20) + 0.99, $currency);

        return [
            'code' => $this->faker->regexify('[A-Z0-9]{6}'),
            'email' => $this->faker->unique()->safeEmail,
            'currency' => $currency,
            'shipping_price_initial' => $shipping_price,
            'shipping_price' => $shipping_price,
            'comment' => mt_rand(0, 9) ? null : $this->faker->text,
            'invoice_requested' => $this->faker->boolean,
        ];
    }
}
