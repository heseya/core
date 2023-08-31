<?php

namespace Database\Factories;

use App\Models\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class AppFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = App::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->name;

        return [
            'url' => $this->faker->unique()->url,
            'microfrontend_url' => $this->faker->unique()->url,
            'name' => $name,
            'slug' => Str::slug($name),
            'version' => mt_rand(0, 9) . '.' . mt_rand(0, 9) . '.' . mt_rand(0, 9),
            'api_version' => '^' . Config::get('api.version'),
        ];
    }
}
