<?php

namespace Database\Factories;

use App\Models\ProductSet;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductSetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductSet::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->words(mt_rand(1, 4), true);

        return [
            'name' => $name,
            'slug' => Str::of($name)->slug() . '-' . mt_rand(1, 99999),
            'public' => $this->faker->boolean,
            'public_parent' => true,
            'description_html' => '<p>' . $this->faker->sentence(10) . '</p>',
        ];
    }
}
