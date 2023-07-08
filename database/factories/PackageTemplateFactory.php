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
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name . ' package',
            'weight' => mt_rand(1, 100) / 10.0,
            'width' => mt_rand(1, 100),
            'height' => mt_rand(1, 100),
            'depth' => mt_rand(1, 100),
        ];
    }
}
