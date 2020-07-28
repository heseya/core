<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Laravel\Passport\Passport;
use Tests\TestCase;

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
            'id' => $this->payment_method->getKey(),
            'name' => $this->payment_method->name,
            'public' => $this->payment_method->public,
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->getJson('/payment-methods');
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
        $response = $this->postJson('/payment-methods');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $payment_method = [
            'name' => 'Test',
            'alias' => 'test',
            'public' => true,
        ];

        $response = $this->postJson('/payment-methods', $payment_method);
        $response
            ->assertCreated()
            ->assertJson(['data' => $payment_method]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patchJson('/payment-methods/id:' . $this->payment_method->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $payment_method = [
            'name' => 'Test 2',
            'alias' => 'test2',
            'public' => false,
        ];

        $response = $this->patchJson(
            '/payment-methods/id:' . $this->payment_method->getKey(),
            $payment_method,
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $payment_method]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->deleteJson('/payment-methods/id:' . $this->payment_method->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->deleteJson('/payment-methods/id:' . $this->payment_method->getKey());
        $response->assertNoContent();
    }
}
