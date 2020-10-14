<?php

namespace Database\Factories;

use App\Models\Order;
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
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => $this->faker->regexify('[A-Z0-9]{6}'),
            'email' => $this->faker->unique()->safeEmail,
            'currency' => rand(0, 9) < 1 ? $this->faker->currencyCode : 'PLN',
            'shipping_price' => rand(8, 20) + 0.99,
        ];
    }
}
