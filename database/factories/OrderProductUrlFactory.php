<?php

namespace Database\Factories;

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
