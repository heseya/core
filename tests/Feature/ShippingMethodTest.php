<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\ShippingMethod;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ShippingMethodTest extends TestCase
{
    public ShippingMethod $shipping_method;
    public ShippingMethod $shipping_method_hidden;

    public array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->shipping_method = ShippingMethod::factory()->create([
            'public' => true,
        ]);

        $this->shipping_method_hidden = ShippingMethod::factory()->create([
            'public' => false,
        ]);

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->shipping_method->getKey(),
            'name' => $this->shipping_method->name,
            'price' => $this->shipping_method->price,
            'public' => $this->shipping_method->public,
        ];
    }

    public function testIndex(): void
    {
        $response = $this->getJson('/shipping-methods');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public shipping methods.
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    public function testCreate(): void
    {
        $response = $this->postJson('/shipping-methods');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $shipping_method = [
            'name' => 'Test',
            'price' => 1.23,
            'public' => true,
        ];

        $response = $this->postJson('/shipping-methods', $shipping_method);
        $response
            ->assertCreated()
            ->assertJson(['data' => $shipping_method]);

        $this->assertDatabaseHas('shipping_methods', $shipping_method);
    }

    public function testUpdate(): void
    {
        $response = $this->patchJson('/shipping-methods/id:' . $this->shipping_method->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $shipping_method = [
            'name' => 'Test 2',
            'price' => 5.23,
            'public' => false,
        ];

        $response = $this->patchJson(
            '/shipping-methods/id:' . $this->shipping_method->getKey(),
            $shipping_method,
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $shipping_method]);

        $this->assertDatabaseHas(
            'shipping_methods',
            $shipping_method + ['id' => $this->shipping_method->getKey()],
        );
    }

    public function testDelete(): void
    {
        $response = $this->deleteJson('/shipping-methods/id:' . $this->shipping_method->getKey());
        $response->assertUnauthorized();
        $this->assertDatabaseHas('shipping_methods', $this->shipping_method->toArray());

        Passport::actingAs($this->user);

        $response = $this->deleteJson('/shipping-methods/id:' . $this->shipping_method->getKey());
        $response->assertNoContent();
        $this->assertDeleted($this->shipping_method);
    }

    public function testDeleteWithRelations(): void
    {
        Passport::actingAs($this->user);

        $this->shipping_method = ShippingMethod::factory()->create();

        Order::factory()->create([
            'shipping_method_id' => $this->shipping_method->getKey(),
        ]);

        $response = $this->deleteJson('/shipping-methods/id:' . $this->shipping_method->getKey());
        $response->assertStatus(409);
        $this->assertDatabaseHas('shipping_methods', $this->shipping_method->toArray());
    }
}
