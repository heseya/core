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
        $name = $this->faker->unique()->word;

        return [
            'name' => $name,
            'slug' => Str::of($name)->slug(),
            'public' => $this->faker->boolean,
            'hide_on_index' => $this->faker->boolean,
            'parent_id' => rand(0, 3) ? null : ProductSet::inRandomOrder()->first()->getKey(),
        ];
    }
}
