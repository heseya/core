<?php

namespace Tests\Feature;

use App\Models\ShippingMethod;
use Tests\TestCase;

class ShippingMethodReorderTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testReorderUnauthorized($user): void
    {
        $shippingMethod1 = ShippingMethod::factory()->create();
        $shippingMethod2 = ShippingMethod::factory()->create();
        $shippingMethod3 = ShippingMethod::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/shipping-methods/reorder', [
                $shippingMethod1->getKey(),
                $shippingMethod3->getKey(),
                $shippingMethod2->getKey(),
            ])
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testReorder($user): void
    {
        $this->{$user}->givePermissionTo('shipping_methods.edit');

        $shippingMethod1 = ShippingMethod::factory()->create();
        $shippingMethod2 = ShippingMethod::factory()->create();
        $shippingMethod3 = ShippingMethod::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/shipping-methods/reorder', ['shipping_methods' => [
                $shippingMethod1->getKey(),
                $shippingMethod3->getKey(),
                $shippingMethod2->getKey(),
            ],
            ])
            ->assertNoContent();

        $this
            ->assertDatabaseHas('shipping_methods', [
                'id' => $shippingMethod1->getKey(),
                'order' => 0,
            ])
            ->assertDatabaseHas('shipping_methods', [
                'id' => $shippingMethod3->getKey(),
                'order' => 1,
            ])
            ->assertDatabaseHas('shipping_methods', [
                'id' => $shippingMethod2->getKey(),
                'order' => 2,
            ]);
    }
}
