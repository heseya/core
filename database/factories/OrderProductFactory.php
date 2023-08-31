<?php

namespace Database\Factories;

use App\Models\OrderProduct;
use App\Models\Product;
use Brick\Money\Money;
use Domain\Currency\Currency;

class OrderProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OrderProduct::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
//        $currency = Currency::getRandomValue();
        $currency = Currency::DEFAULT->value;
        $price = Money::of(mt_rand(100, 200), $currency);

        return [
            'name' => $this->faker->word,
            'quantity' => mt_rand(1, 4),
            'currency' => $currency,
            'price' => $price,
            'price_initial' => $price,
            'base_price' => $price,
            'base_price_initial' => $price,
            'product_id' => Product::select('id')->inRandomOrder()->first()->getKey(),
        ];
    }
}
