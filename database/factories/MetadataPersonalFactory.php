<?php

namespace Database\Factories;

use Domain\Metadata\Enums\MetadataType;
use Domain\Metadata\Models\MetadataPersonal;

class MetadataPersonalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MetadataPersonal::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'value' => $this->faker->word,
            'value_type' => MetadataType::getRandomInstance(),
        ];
    }
}
