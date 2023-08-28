<?php

namespace Database\Factories;

use Domain\Tag\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'new',
                'sale',
                'limited',
                'preorder',
                'limited to 100',
                'coming soon',
            ]),
            'color' => ltrim($this->faker->hexColor, '#'),
        ];
    }
}
