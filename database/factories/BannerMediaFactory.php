<?php

namespace Database\Factories;

use Domain\Banner\Models\BannerMedia;
use Illuminate\Support\Facades\App;

/**
 * @extends Factory<BannerMedia>
 */
class BannerMediaFactory extends Factory
{
    /**
     * @var string
     */
    protected $model = BannerMedia::class;

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
            'published' => [App::getLocale()],
        ];
    }
}
