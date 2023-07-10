<?php

namespace Database\Factories;

use App\Models\Option;
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
            'price' => mt_rand(0, 1) ? $this->faker->numberBetween(0, 100) : 0,
            'disabled' => mt_rand(0, 10) === 0,
        ];
    }
}
