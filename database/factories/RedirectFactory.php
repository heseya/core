<?php

namespace Database\Factories;

use Domain\Redirect\Enums\RedirectType;
use Domain\Redirect\Models\Redirect;
use Illuminate\Database\Eloquent\Factories\Factory;

class RedirectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Redirect::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->slug,
            'url' => $this->faker->url,
            'type' => $this->faker->randomElement([RedirectType::TEMPORARY->value, RedirectType::PERMANENT->value]),
        ];
    }
}
