<?php

namespace Database\Factories;

use App\Models\PackageTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PackageTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name . ' package',
            'weight' => rand(1, 100) / 10.0,
            'width' => rand(1, 100),
            'height' => rand(1, 100),
            'depth' => rand(1, 100),
        ];
    }
}
