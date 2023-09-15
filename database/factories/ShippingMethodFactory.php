<?php

namespace Database\Factories;

use App\Enums\ShippingType;
use Domain\ShippingMethod\Models\ShippingMethod;

class ShippingMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ShippingMethod::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'dpd_pickup',
                'dpd',
                'inpost_kurier',
                'inpost',
            ]),
            'public' => $this->faker->boolean,
            'is_product_blocklist' => $this->faker->boolean,
        ];
    }

    public function allMethods(): static
    {
        return $this->forEachSequence(
            [
                'name' => 'dpd_pickup',
                'shipping_type' => ShippingType::POINT_EXTERNAL,
            ],
            //            [
            //                'name' => 'dpd',
            //               'shipping_type' => ShippingType::ADDRESS,
            //            ],
            [
                'name' => 'inpost_kurier',
                'shipping_type' => ShippingType::ADDRESS,
            ],
            [
                'name' => 'inpost',
                'shipping_type' => ShippingType::POINT,
            ],
            [
                'name' => 'digital',
                'shipping_type' => ShippingType::DIGITAL,
            ]
        );
    }
}
