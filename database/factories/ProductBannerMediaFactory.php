<?php

namespace Database\Factories;

use Domain\Product\Models\ProductBannerMedia;
use Illuminate\Support\Facades\App;

class ProductBannerMediaFactory extends Factory
{
    /** @var string */
    protected $model = ProductBannerMedia::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'url' => $this->faker->imageUrl(),
            'title' => $this->faker->word,
            'subtitle' => $this->faker->word,
        ];
    }
}
