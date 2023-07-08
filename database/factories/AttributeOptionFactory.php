<?php

namespace Database\Factories;

use App\Models\AttributeOption;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttributeOptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AttributeOption::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'value_number' => mt_rand(0, 1) === 1 ? $this->faker->randomNumber(5) : null,
            'value_date' => $this->faker->date,
        ];
    }
}
