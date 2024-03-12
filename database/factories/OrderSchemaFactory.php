<?php

namespace Database\Factories;

use App\Models\OrderSchema;
use Brick\Money\Money;
use Domain\Currency\Currency;

class OrderSchemaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OrderSchema::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
//        $currency = Currency::getRandomValue();
        $currency = Currency::DEFAULT->value;
        $price = Money::of(mt_rand(0, 1) ? 0 : $this->faker->numberBetween(0, 100), $currency);

        return [
            'name' => $this->faker->word,
            'value' => mt_rand(0, 1) ? $this->faker->sentence : (mt_rand(0, 1) ? $this->faker->boolean : $this->faker->randomNumber),
            'currency' => $currency,
            'price' => $price,
            'price_initial' => $price,
        ];
    }
}
