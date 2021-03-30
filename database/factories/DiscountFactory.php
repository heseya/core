<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Discount::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'code' => $this->faker->regexify('[A-Z0-9]{8}'),
            'description' => rand(0, 1) ? $this->faker->text : null,
            'discount' => $this->faker->randomFloat(),
            'type' => DiscountType::getRandomValue(),
        ];
    }
}
