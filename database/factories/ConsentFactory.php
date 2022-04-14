<?php

namespace Database\Factories;

use App\Models\Consent;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Consent::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->sentence(rand(1, 3)),
            'description_html' => $this->faker->unique()->sentence(rand(1, 3)),
            'required' => $this->faker->boolean(),
        ];
    }
}
