<?php

namespace Tests\Feature;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Models\App;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    public PaymentMethod $payment_method;
    public PaymentMethod $payment_method_related;
    public PaymentMethod $payment_method_hidden;
    public ShippingMethod $shipping_method;

    public App $application;

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
    public function testShow($user): void
    {
        $this->$user->givePermissionTo('payment_methods.show_details');

        PaymentMethod::query()->delete();
        $paymentMethod = PaymentMethod::factory()->create();

        $response = $this->actingAs($this->$user)->getJson('/payment-methods/id:' . $paymentMethod->getKey());

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $paymentMethod->getKey(),
                    'name' => $paymentMethod->name,
                    'icon' => $paymentMethod->icon,
                    'alias' => $paymentMethod->alias,
                    'public' => $paymentMethod->public,
                    'url' => $paymentMethod->url,
                ],
            ]);
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

    public function testCreate(): void
    {
        $this->application->givePermissionTo('payment_methods.add');

        $payment_method = [
            'name' => 'Test',
            'alias' => 'test',
            'public' => true,
            'url' => 'http://test.com',
            'icon' => 'test icon'
        ];

        $response = $this->actingAs($this->application)
            ->postJson('/payment-methods', $payment_method);

        $response
            ->assertCreated()
            ->assertJson(['data' => $payment_method]);
    }

    public function testCreateAsUser(): void
    {
        $this->user->givePermissionTo('payment_methods.add');

        $payment_method = [
            'name' => 'Test',
            'alias' => 'test',
            'public' => true,
            'url' => 'http://test.com',
            'icon' => 'test icon'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/payment-methods', $payment_method);

        $response
            ->assertStatus(Exceptions::getCode(Exceptions::CLIENT_USERS_NO_ACCESS))
            ->assertJsonFragment(['key' => Exceptions::coerce(Exceptions::CLIENT_USERS_NO_ACCESS)->key]);
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

    public function testUpdate(): void
    {
        $this->application->givePermissionTo('payment_methods.edit');

        $payment_method = [
            'name' => 'Test 2',
            'alias' => 'test2',
            'public' => false,
        ];

        $response = $this->actingAs($this->application)->patchJson(
            '/payment-methods/id:' . $this->payment_method->getKey(),
            $payment_method,
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $payment_method]);
    }

    public function testUpdateWithoutChange(): void
    {
        $this->application->givePermissionTo('payment_methods.edit');

        $response = $this->actingAs($this->application)->patchJson(
            '/payment-methods/id:' . $this->payment_method->getKey(),
            [],
        );

        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->payment_method->id,
                'name' => $this->payment_method->name,
                'alias' => $this->payment_method->alias,
                'public' => $this->payment_method->public,
                'icon' => $this->payment_method->icon,
                'url' => $this->payment_method->url,
            ]]);
    }

    public function testUpdateAsUser(): void
    {
        $this->user->givePermissionTo('payment_methods.edit');

        $payment_method = [
            'name' => 'Test 2',
            'alias' => 'test2',
            'public' => false,
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/payment-methods/id:' . $this->payment_method->getKey(),
            $payment_method,
        );
        $response
            ->assertStatus(Exceptions::getCode(Exceptions::CLIENT_USERS_NO_ACCESS))
            ->assertJsonFragment(['key' => Exceptions::coerce(Exceptions::CLIENT_USERS_NO_ACCESS)->key]);
    }

    public function testDeleteUnauthorized(): void
    {
        $response = $this->deleteJson('/payment-methods/id:' . $this->payment_method->getKey());
        $response->assertForbidden();
    }

    public function testDelete(): void
    {
        $this->application->givePermissionTo('payment_methods.remove');

        $response = $this->actingAs($this->application)
            ->deleteJson('/payment-methods/id:' . $this->payment_method->getKey());
        $response->assertNoContent();
    }

    public function testDeleteAsUser(): void
    {
        $this->user->givePermissionTo('payment_methods.remove');

        $response = $this->actingAs($this->user)
            ->deleteJson('/payment-methods/id:' . $this->payment_method->getKey());

        $response
            ->assertStatus(Exceptions::getCode(Exceptions::CLIENT_USERS_NO_ACCESS))
            ->assertJsonFragment(['key' => Exceptions::coerce(Exceptions::CLIENT_USERS_NO_ACCESS)->key]);
    }
}
