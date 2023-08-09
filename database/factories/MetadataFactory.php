<?php

namespace Database\Factories;

use App\Enums\MetadataType;
use App\Models\Metadata;
use Illuminate\Database\Eloquent\Factories\Factory;

class MetadataFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Metadata::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'value' => $this->faker->word,
            'value_type' => MetadataType::getRandomInstance(),
            'public' => $this->faker->boolean,
        ];
    }
}
