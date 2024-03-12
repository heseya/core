<?php

namespace Database\Factories;

use Domain\ProductSet\ProductSet;
use Illuminate\Support\Facades\App;

class ProductSetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ProductSet>
     */
    protected $model = ProductSet::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(mt_rand(1, 4), true),
            'slug' => $this->faker->unique()->slug,
            'public' => $this->faker->boolean,
            'public_parent' => true,
            'description_html' => '<p>' . $this->faker->sentence(10) . '</p>',
            'published' => [App::getLocale()],
        ];
    }
}
