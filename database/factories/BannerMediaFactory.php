<?php

namespace Database\Factories;

use App\Models\BannerMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BannerMedia>
 */
class BannerMediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $last = BannerMedia::reversed()->first();
        $order = $last ? $last->order + 1 : 0;

        return [
            'url' => $this->faker->imageUrl(),
            'title' => $this->faker->word,
            'subtitle' => $this->faker->word,
            'order' => $order,
        ];
    }
}
