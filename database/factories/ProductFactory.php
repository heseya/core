<?php

namespace Database\Factories;

use App\Models\Price;
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
     */
    public function definition(): array
    {
        $name = $this->faker->sentence(rand(1, 3));

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . rand(1, 99999),
            'description_html' => '<p>' . $this->faker->sentence(10) . '</p>',
            'description_short' => $this->faker->sentence(10),
            'public' => $this->faker->boolean,
        ];
    }

    public function configure(): ProductFactory
    {
        return $this->afterCreating(function (Product $product) {
            $product->price()->save(
                Price::factory()->make(),
            );
        });
    }
}
