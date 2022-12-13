<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
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

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/payment-methods');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('payment_methods.show');

        $response = $this->actingAs($this->$user)->getJson('/payment-methods');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data') // Should show only public payment methods.
            ->assertJsonFragment(['id' => $this->payment_method->getKey()])
            ->assertJsonFragment(['id' => $this->payment_method_related->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByOrderCode($user): void
    {
        $this->$user->givePermissionTo('payment_methods.show');

        $order = Order::factory()->create([
            'shipping_method_id' => $this->shipping_method->getKey(),
        ]);

        $this->actingAs($this->$user)
            ->json('GET', '/payment-methods', ['order_code' => $order->code])
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public payment methods.
            ->assertJsonFragment(['id' => $this->payment_method_related->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByOrderCodeDigitalShipping($user): void
    {
        $this->$user->givePermissionTo('payment_methods.show');

        $order = Order::factory()->create([
            'digital_shipping_method_id' => $this->shipping_method->getKey(),
        ]);

        $this->actingAs($this->$user)
            ->json('GET', '/payment-methods', ['order_code' => $order->code])
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public payment methods.
            ->assertJsonFragment(['id' => $this->payment_method_related->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByOrderCodeOnlyPhysicalShipping($user): void
    {
        $this->$user->givePermissionTo('payment_methods.show');

        $digitalShipping = ShippingMethod::factory()->create(['public' => true]);
        $digitalPayment = PaymentMethod::factory()->create(['public' => true]);
        $digitalPayment->shippingMethods()->attach($digitalShipping);

        $order = Order::factory()->create([
            'shipping_method_id' => $this->shipping_method->getKey(),
            'digital_shipping_method_id' => $digitalShipping->getKey(),
        ]);

        $this->actingAs($this->$user)
            ->json('GET', '/payment-methods', ['order_code' => $order->code])
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public payment methods.
            ->assertJsonFragment(['id' => $this->payment_method_related->getKey()])
            ->assertJsonMissing(['id' => $digitalPayment->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByShippingMethod($user): void
    {
        $this->$user->givePermissionTo('payment_methods.show');

        $this->actingAs($this->$user)
            ->json('GET', '/payment-methods', ['shipping_method_id' => $this->shipping_method->getKey()])
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public payment methods.
            ->assertJsonFragment(['id' => $this->payment_method_related->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden($user): void
    {
        $this->$user->givePermissionTo(['payment_methods.show', 'payment_methods.show_hidden']);

        $response = $this->actingAs($this->$user)->getJson('/payment-methods');
        $response
            ->assertOk()
            ->assertJsonCount(3, 'data') // Should show only public payment methods.
            ->assertJsonFragment(['id' => $this->payment_method->getKey()])
            ->assertJsonFragment(['id' => $this->payment_method_related->getKey()])
            ->assertJsonFragment(['id' => $this->payment_method_hidden->getKey()]);
    }

    public function testCreateUnauthorized(): void
    {
        $payment_method = [
            'name' => 'Test',
            'alias' => 'test',
            'public' => true,
        ];

        $response = $this->postJson('/payment-methods', $payment_method);
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('payment_methods.add');

        $payment_method = [
            'name' => 'Test',
            'alias' => 'test',
            'public' => true,
        ];

        $response = $this->actingAs($this->$user)
            ->postJson('/payment-methods', $payment_method);
        $response
            ->assertCreated()
            ->assertJson(['data' => $payment_method]);
    }

    public function testUpdateUnauthorized(): void
    {
        $payment_method = [
            'name' => 'Test 2',
            'alias' => 'test2',
            'public' => false,
        ];

        $response = $this->patchJson(
            '/payment-methods/id:' . $this->payment_method->getKey(),
            $payment_method,
        );
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->$user->givePermissionTo('payment_methods.edit');

        $payment_method = [
            'name' => 'Test 2',
            'alias' => 'test2',
            'public' => false,
        ];

        $response = $this->actingAs($this->$user)->patchJson(
            '/payment-methods/id:' . $this->payment_method->getKey(),
            $payment_method,
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $payment_method]);
    }

    public function testDeleteUnauthorized(): void
    {
        $response = $this->deleteJson('/payment-methods/id:' . $this->payment_method->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('payment_methods.remove');

        $response = $this->actingAs($this->$user)
            ->deleteJson('/payment-methods/id:' . $this->payment_method->getKey());
        $response->assertNoContent();
    }
}
