<?php

namespace Database\Factories;

use App\Models\WebHook;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebHookFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WebHook::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->sentence(rand(1, 3)),
            'url' => $this->faker->url,
            'secret' => Str::random(25),
            'with_issuer' => $this->faker->boolean,
            'with_hidden' => $this->faker->boolean,
            'events' => $this->faker->words,
        ];
    }
}
