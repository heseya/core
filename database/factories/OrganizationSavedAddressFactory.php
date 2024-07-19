<?php

namespace Database\Factories;

use App\Enums\SavedAddressType;
use Domain\Organization\Models\OrganizationSavedAddress;

class OrganizationSavedAddressFactory extends Factory
{
    protected $model = OrganizationSavedAddress::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'type' => SavedAddressType::SHIPPING,
            'default' => false,
        ];
    }
}
