<?php

namespace Database\Factories;

use App\Models\OrderSchema;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        $price = mt_rand(0, 1) ? 0 : $this->faker->numberBetween(0, 100);

        return [
            'name' => $this->faker->word,
            'value' => mt_rand(0, 1) ? $this->faker->sentence : (mt_rand(0, 1) ? $this->faker->boolean : $this->faker->randomNumber),
            'price' => $price,
            'price_initial' => $price,
        ];
    }
}
