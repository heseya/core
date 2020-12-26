<?php

namespace Database\Factories;

use App\Models\ProductSchemaItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductSchemaItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductSchemaItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'extra_price' => rand(0, 100),
        ];
    }
}
