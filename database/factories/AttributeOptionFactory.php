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
     *
     * @return array
     */
    public function definition()
    {
        return [
            'value_text' => $this->faker->sentence,
            'value' => rand(0, 1) === 1 ? $this->faker->randomNumber(5) : null,
        ];
    }
}
