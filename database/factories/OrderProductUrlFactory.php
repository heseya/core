<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderProductUrlFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->domainName(),
            'url' => $this->faker->url(),
        ];
    }
}
