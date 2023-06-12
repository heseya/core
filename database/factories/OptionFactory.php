<?php

namespace Database\Factories;

use App\Models\Option;
use App\Models\Price;
use Illuminate\Database\Eloquent\Factories\Factory;

class OptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Option::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'disabled' => rand(0, 10) === 0,

            //            'price' => rand(0, 1) ? $this->faker->numberBetween(0, 100) : 0,
        ];
    }

    public function configure(): OptionFactory
    {
        return $this->afterCreating(function (Option $option) {
            $option->price()->save(
                Price::factory()->make(),
            );
        });
    }
}
