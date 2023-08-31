<?php

namespace Database\Factories;

use App\Models\Status;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class StatusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Status::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word,
            'color' => ltrim($this->faker->hexcolor, '#'),
            'description' => Str::limit($this->faker->paragraph, 220),
            'published' => [App::getLocale()],
        ];
    }
}
