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
            'source_url' => $this->faker->slug,
            'target_url' => $this->faker->url,
            'type' => $this->faker->randomElement([
                RedirectType::TEMPORARY_REDIRECT->value,
                RedirectType::PERMANENT_REDIRECT->value,
                RedirectType::MOVED_PERMANENTLY->value,
                RedirectType::FOUND->value,
            ]),
            'enabled' => $this->faker->boolean,
        ];
    }
}
