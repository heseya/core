<?php

namespace Database\Factories;

use App\Models\ProductSchema;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductSchemaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductSchema::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->randomElement([
                'Łańcuszek',
                'Zawieszka',
                'Grawer',
                'Typ',
                'Kolor',
            ]),
            'type' => $this->faker->boolean,
            'required' => $this->faker->boolean,
        ];
    }
}
