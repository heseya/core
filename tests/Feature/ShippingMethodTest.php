<?php

namespace Tests\Feature;

use App\Models\Order;
use Tests\TestCase;
use App\Models\ShippingMethod;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShippingMethodTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->shipping_method = factory(ShippingMethod::class)->create([
            'public' => true,
        ]);

        $this->shipping_method_hidden = factory(ShippingMethod::class)->create([
            'public' => false,
        ]);

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->shipping_method->id,
            'name' => $this->shipping_method->name,
            'price' => $this->shipping_method->price,
            'public' => $this->shipping_method->public,
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('/shipping-methods');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Shoud show only public shipping methods.
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @return void
     */
    public function testCreate()
    {
        $response = $this->post('/shipping-methods');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $shipping_method = [
            'name' => 'Test',
            'price' => 1.23,
            'public' => true,
        ];

        $response = $this->post('/shipping-methods', $shipping_method);

        $response
            ->assertCreated()
            ->assertJson(['data' => $shipping_method]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patch('/shipping-methods/id:' . $this->shipping_method->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $shipping_method = [
            'name' => 'Test 2',
            'price' => 5.23,
            'public' => false,
        ];

        $response = $this->patch(
            '/shipping-methods/id:' . $this->shipping_method->id,
            $shipping_method,
        );

        $response
            ->assertStatus(200)
            ->assertJson(['data' => $shipping_method]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->delete('/shipping-methods/id:' . $this->shipping_method->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->delete('/shipping-methods/id:' . $this->shipping_method->id);
        $response->assertStatus(204);
    }

    /**
     * @return void
     */
    public function testDeleteWithRelations()
    {
        Passport::actingAs($this->user);

        $this->shipping_method = factory(ShippingMethod::class)->create();

        factory(Order::class)->create([
            'shipping_method_id' => $this->shipping_method->id,
        ]);

        $response = $this->delete('/shipping-methods/id:' . $this->shipping_method->id);

        $response->assertStatus(400);
    }
}
