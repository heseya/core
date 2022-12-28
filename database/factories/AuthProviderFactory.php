<?php

namespace Database\Factories;

use App\Enums\AuthProviderKey;
use App\Models\AuthProvider;
use Illuminate\Database\Eloquent\Factories\Factory;


class AuthProviderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AuthProvider::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'key' => AuthProviderKey::getRandomValue(),
            'active' => $this->faker->boolean,
            'client_id' => $this->faker->unique()->word,
            'client_secret' => $this->faker->unique()->word,
        ];
    }
}
