<?php

namespace Database\Factories;

use Domain\Seo\Models\SeoMetadata;
use Illuminate\Support\Facades\App;

class SeoMetadataFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SeoMetadata::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->word,
            'description' => $this->faker->sentence,
            'keywords' => [
                App::getLocale() => $this->faker->words(),
            ],
            'no_index' => $this->faker->boolean,
            'published' => [App::getLocale()],
        ];
    }
}
