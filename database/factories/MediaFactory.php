<?php

namespace Database\Factories;

use App\Enums\MediaSource;
use App\Enums\MediaType;
use App\Models\Media;

class MediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Media::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'type' => MediaType::PHOTO,
            'url' => 'https://picsum.photos/seed/' . mt_rand(0, 999999) . '/800',
            'source' => MediaSource::SILVERBOX,
        ];
    }
}
