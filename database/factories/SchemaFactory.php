<?php

namespace Database\Factories;

use App\Models\Schema;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\App;

class SchemaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Schema::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence(10),
            'price' => rand(0, 2) ? 0 : rand(10, 40),
            'hidden' => rand(0, 10) === 0,
            'required' => $this->faker->boolean,
            'max' => null,
            'min' => null,
            'default' => null,
            'pattern' => null,
            'validation' => null,
            'published' => [App::getLocale() => $this->faker->boolean],
        ];
    }
}
