<?php

namespace Database\Factories;

use App\Models\Price;
use App\Models\Schema;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'hidden' => mt_rand(0, 10) === 0,
            'required' => $this->faker->boolean,
            'max' => null,
            'min' => null,
            'default' => null,
            'pattern' => null,
            'validation' => null,

            //            'price' => rand(0, 2) ? 0 : rand(10, 40),
        ];
    }

    public function configure(): SchemaFactory
    {
        return $this->afterCreating(function (Schema $schema) {
            $schema->price()->save(
                Price::factory()->make(),
            );
        });
    }
}
