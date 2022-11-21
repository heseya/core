<?php

namespace Tests\Feature;

use App\Events\SendOrderUrls;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Notifications\SendUrls;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrderProductTest extends TestCase
{
    use WithFaker;

    private Order $order;
    private OrderProduct $digitalProduct;
    private OrderProduct $product;

    public function setUp(): void
    {
        parent::setUp();

        $shippingMethod = ShippingMethod::factory()->create();
        $status = Status::factory()->create();
        $product = Product::factory()->create([
            'public' => true,
            'is_digital' => false,
        ]);
        $digitalProduct = Product::factory()->create([
            'public' => true,
            'is_digital' => true,
        ]);

        $this->order = Order::factory()->create([
            'shipping_method_id' => $shippingMethod->getKey(),
            'status_id' => $status->getKey(),
            'email' => $this->faker->freeEmail,
        ]);

        $this->user->orders()->save($this->order);

        $this->digitalProduct = $this->order->products()->create([
            'product_id' => $digitalProduct->getKey(),
            'quantity' => 1,
            'price' => 247.47,
            'price_initial' => 247.47,
            'name' => $digitalProduct->name,
            'shipping_digital' => true,
        ]);

        $this->product = $this->order->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'price' => 300,
            'price_initial' => 300,
            'name' => $product->name,
            'shipping_digital' => false,
        ]);
    }

    public function testMyProductsUnauthorized(): void
    {
        $this
            ->json('GET', '/orders/my-products')
            ->assertForbidden();
    }

    public function testIndexMyProducts(): void
    {
        $this
            ->actingAs($this->user)
            ->json('GET', '/orders/my-products')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function testIndexMyProductsOnlyDigital(): void
    {
        $this
            ->actingAs($this->user)
            ->json('GET', '/orders/my-products?shipping_digital')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $this->digitalProduct->getKey(),
                'shipping_digital' => true,
            ])
            ->assertJsonMissing([
                'id' => $this->product->getKey(),
                'shipping_digital' => false,
            ]);
    }

    public function testIndexMyProductsOnlyPhysical(): void
    {
        $this
            ->actingAs($this->user)
            ->json('GET', '/orders/my-products', ['shipping_digital' => false])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing([
                'id' => $this->digitalProduct->getKey(),
                'shipping_digital' => true,
            ])
            ->assertJsonFragment([
                'id' => $this->product->getKey(),
                'shipping_digital' => false,
            ]);
    }

    public function testOrderSendUrlsUnauthorized(): void
    {
        $this
            ->json('POST', '/orders/id:' . $this->order->getKey() . '/send-urls')
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderNotSendUrls($user): void
    {
        $this->$user->givePermissionTo('orders.show_details');

        Event::fake([SendOrderUrls::class]);
        Mail::fake();

        $this
            ->actingAs($this->$user)
            ->json(
                'POST',
                '/orders/id:' . $this->order->getKey() . '/send-urls'
            )->assertOk();

        $this->assertDatabaseHas('order_products', [
            'id' => $this->digitalProduct->getKey(),
            'is_delivered' => false,
        ]);

        Event::assertNotDispatched(SendOrderUrls::class);
        Mail::assertNotSent(SendUrls::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderSendUrls($user): void
    {
        $this->$user->givePermissionTo('orders.show_details');

        $this->digitalProduct->urls()->create([
            'name' => 'first_url',
            'url' => 'https://example-first-url.com',
        ]);

        Event::fake([SendOrderUrls::class]);
        Mail::fake();

        $this
            ->actingAs($this->$user)
            ->json(
                'POST',
                '/orders/id:' . $this->order->getKey() . '/send-urls'
            )->assertOk();

        $this->assertDatabaseHas('order_products', [
            'id' => $this->digitalProduct->getKey(),
            'is_delivered' => true,
        ]);

        Event::assertDispatched(SendOrderUrls::class);
    }
}
