<?php

namespace Database\Factories;

use Domain\Organization\Enums\OrganizationStatus;
use Domain\Organization\Models\Organization;

class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'phone' => $this->faker->phoneNumber,
            'status' => OrganizationStatus::getRandomInstance(),
            'email' => $this->faker->email,
            'description' => $this->faker->text,
        ];
    }
}
