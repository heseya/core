<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Laravel\Passport\Passport;

class PaymentMethodTest extends TestCase
{
    public PaymentMethod $payment_method;
    public PaymentMethod $payment_method_related;
    public PaymentMethod $payment_method_hidden;
    public ShippingMethod $shipping_method;
    public array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->payment_method = factory(PaymentMethod::class)->create([
            'public' => true,
        ]);

        $this->payment_method_related = factory(PaymentMethod::class)->create([
            'public' => true,
        ]);

        $this->shipping_method = factory(ShippingMethod::class)->create([
            'public' => true,
        ]);

        $this->payment_method_related->shippingMethods()->attach($this->shipping_method);

        $this->payment_method_hidden = factory(PaymentMethod::class)->create([
            'public' => false,
        ]);

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->payment_method->id,
            'name' => $this->payment_method->name,
            'public' => $this->payment_method->public,
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('/payment-methods');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data') // Should show only public payment methods.
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @return void
     */
    public function testCreate()
    {
        $response = $this->post('/payment-methods');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $payment_method = [
            'name' => 'Test',
            'alias' => 'test',
            'public' => true,
        ];

        $response = $this->post('/payment-methods', $payment_method);
        $response
            ->assertCreated()
            ->assertJson(['data' => $payment_method]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patch('/payment-methods/id:' . $this->payment_method->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $payment_method = [
            'name' => 'Test 2',
            'alias' => 'test2',
            'public' => false,
        ];

        $response = $this->patch(
            '/payment-methods/id:' . $this->payment_method->id,
            $payment_method,
        );
        $response
            ->assertStatus(200)
            ->assertJson(['data' => $payment_method]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->delete('/payment-methods/id:' . $this->payment_method->id);
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->delete('/payment-methods/id:' . $this->payment_method->id);
        $response->assertStatus(204);
    }
}
