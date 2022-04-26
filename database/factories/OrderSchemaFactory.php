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
     *
     * @return array
     */
    public function definition(): array
    {
        $price = rand(0, 1) ? 0 : $this->faker->numberBetween(0, 100);
        return [
            'name' => $this->faker->word,
            'value' => rand(0, 1) ? $this->faker->sentence : (rand(0, 1) ? $this->faker->boolean : $this->faker->randomNumber),
            'price' => $price,
            'price_initial' => $price,
        ];
    }
}
