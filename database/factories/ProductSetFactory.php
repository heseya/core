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

        $last = ProductSet::reversed()->first();
        $order = $last ? $last->order + 1 : 0;

        return [
            'name' => $name,
            'slug' => Str::of($name)->slug(),
            'public' => $this->faker->boolean,
            'public_parent' => true,
            'order' => $order,
            'hide_on_index' => $this->faker->boolean,
            'description_html' => '<p>' . $this->faker->sentence(10) . '</p>',
        ];
    }
}
