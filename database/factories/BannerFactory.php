<?php

namespace Database\Factories;

use Domain\Banner\Models\Banner;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = Banner::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'slug' => $this->faker->slug,
            //            'url' => $this->faker->imageUrl(),
            'name' => $this->faker->word,
            'active' => $this->faker->boolean,
        ];
    }
}
