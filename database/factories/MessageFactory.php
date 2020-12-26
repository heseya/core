<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'external_id' => $this->faker->uuid,
            'content' => $this->faker->sentence(rand(1, 10)),
            'user_id' => rand(0, 1) ? User::select('id')->inRandomOrder()->first()->getKey() : null,
        ];
    }
}
