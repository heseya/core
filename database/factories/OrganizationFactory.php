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
            'change_version' => $this->faker->randomNumber(1),
            'client_id' => $this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'billing_email' => $this->faker->companyEmail,
            'creator_email' => $this->faker->email,
        ];
    }
}
