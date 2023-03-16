<?php

namespace Database\Factories;

use App\Models\Price;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Price::class;

    /**
     * Define the model's default state.
     *
     * @throws UnknownCurrencyException
    */
    public function definition(): array
    {
        $randomInt = rand(0, 1) ? $this->faker->numberBetween(0, 100) : 0;

        return [
            'value' => Money::of($randomInt, 'PLN'),
        ];
    }
}
