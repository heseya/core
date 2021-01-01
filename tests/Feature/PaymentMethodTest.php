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

        $this->payment_method = PaymentMethod::factory()->create([
            'public' => true,
        ]);

        $this->payment_method_related = PaymentMethod::factory()->create([
            'public' => true,
        ]);
        $this->shipping_method = ShippingMethod::factory()->create([
            'public' => true,
        ]);
        $this->payment_method_related->shippingMethods()->attach($this->shipping_method);

        $this->payment_method_hidden = PaymentMethod::factory()->create([
            'public' => false,
        ]);
    }

    public function testIndex(): void
    {
        $response = $this->getJson('/payment-methods');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data') // Should show only public payment methods.
            ->assertJsonFragment(['id' => $this->payment_method->getKey()])
            ->assertJsonFragment(['id' => $this->payment_method_related->getKey()]);
    }

    public function testCreate(): void
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

    public function testUpdate(): void
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

    public function testDelete(): void
    {
        $response = $this->deleteJson('/payment-methods/id:' . $this->payment_method->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->deleteJson('/payment-methods/id:' . $this->payment_method->getKey());
        $response->assertNoContent();
    }
}
