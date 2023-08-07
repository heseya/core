<?php

namespace Database\Factories;

use App\Models\MetadataPersonal;
use Domain\Metadata\Enums\MetadataType;
use Illuminate\Database\Eloquent\Factories\Factory;

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
