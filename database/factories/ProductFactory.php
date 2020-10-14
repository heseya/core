<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->words(rand(1, 3));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'price' => round(rand(500, 6000), -2),
            'description_md' => $this->faker->sentence(10),
        ];
    }
}
