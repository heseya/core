<?php

namespace Database\Factories;

use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class AttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Attribute>
     */
    protected $model = Attribute::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word;

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence,
            'type' => AttributeType::getRandomInstance(),
            'global' => $this->faker->boolean,
            'sortable' => $this->faker->boolean,
            'published' => [App::getLocale()],
        ];
    }
}
