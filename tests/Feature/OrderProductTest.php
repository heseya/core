<?php

namespace Tests\Feature;

use App\Events\SendOrderUrls;
use App\Mail\OrderUrls;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Status;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\ShippingMethod\Models\ShippingMethod;
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
    private Currency $currency;

    public function setUp(): void
    {
        parent::setUp();

        $shippingMethod = ShippingMethod::factory()->create();
        $status = Status::factory()->create();
        $product = Product::factory()->create([
            'public' => true,
            'shipping_digital' => false,
        ]);
        $digitalProduct = Product::factory()->create([
            'public' => true,
            'shipping_digital' => true,
        ]);

        $this->order = Order::factory()->create([
            'shipping_method_id' => $shippingMethod->getKey(),
            'status_id' => $status->getKey(),
            'email' => $this->faker->freeEmail,
            'paid' => true,
        ]);

        $this->user->orders()->save($this->order);

        $this->currency = Currency::DEFAULT;

        $this->digitalProduct = $this->order->products()->create([
            'product_id' => $digitalProduct->getKey(),
            'quantity' => 1,
            'price' => Money::of(247.47, $this->currency->value),
            'price_initial' => Money::of(247.47, $this->currency->value),
            'base_price' => Money::of(247.47, $this->currency->value),
            'base_price_initial' => Money::of(247.47, $this->currency->value),
            'name' => $digitalProduct->name,
            'shipping_digital' => true,
        ]);

        $this->product = $this->order->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'price' => Money::of(300, $this->currency->value),
            'price_initial' => Money::of(300, $this->currency->value),
            'base_price' => Money::of(300, $this->currency->value),
            'base_price_initial' => Money::of(300, $this->currency->value),
            'name' => $product->name,
            'shipping_digital' => false,
        ]);
    }

    public function testMyProductsUnauthorized(): void
    {
        $this
            ->json('GET', 'my/orders/products')
            ->assertForbidden();
    }

    public function testMyProductsDeprecated(): void
    {
        $this
            ->json('GET', 'orders/my-products')
            ->assertRedirect();
    }

    public function testIndexMyProducts(): void
    {
        $this
            ->actingAs($this->user)
            ->json('GET', 'my/orders/products')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function testIndexMyProductsOnlyDigital(): void
    {
        $this
            ->actingAs($this->user)
            ->json('GET', 'my/orders/products', ['shipping_digital' => true])
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
            ->json('GET', 'my/orders/products', ['shipping_digital' => false])
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

    public function testIndexMyProductsOnlyPaidOrders(): void
    {
        $shippingMethod = ShippingMethod::factory()->create();
        $status = Status::factory()->create();
        $product = Product::factory()->create([
            'public' => true,
            'shipping_digital' => false,
        ]);
        $digitalProduct = Product::factory()->create([
            'public' => true,
            'shipping_digital' => true,
        ]);

        $orderPaid = Order::factory()->create([
            'shipping_method_id' => $shippingMethod->getKey(),
            'status_id' => $status->getKey(),
            'email' => $this->faker->freeEmail,
            'paid' => true,
        ]);

        $orderNoPaid = Order::factory()->create([
            'shipping_method_id' => $shippingMethod->getKey(),
            'status_id' => $status->getKey(),
            'email' => $this->faker->freeEmail,
            'paid' => false,
        ]);

        $this->user->orders()->delete();
        $this->user->orders()->saveMany([$orderPaid, $orderNoPaid]);

        $productPaid = $orderPaid->products()->create([
            'product_id' => $digitalProduct->getKey(),
            'quantity' => 1,
            'price' => Money::of(247.47, $this->currency->value),
            'price_initial' => Money::of(247.47, $this->currency->value),
            'name' => $digitalProduct->name,
            'shipping_digital' => true,
        ]);

        $productNoPaid = $orderNoPaid->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'price' => Money::of(300, $this->currency->value),
            'price_initial' => Money::of(300, $this->currency->value),
            'name' => $product->name,
            'shipping_digital' => false,
        ]);

        $this
            ->actingAs($this->user)
            ->json('GET', 'my/orders/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $productPaid->getKey(),
                'price' => '247.47',
                'currency' => $this->currency,
            ])
            ->assertJsonMissing([
                'id' => $productNoPaid->getKey(),
                'price' => '300.00',
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
        $this->{$user}->givePermissionTo('orders.show_details');

        Event::fake([SendOrderUrls::class]);
        Mail::fake();

        $this
            ->actingAs($this->{$user})
            ->json(
                'POST',
                '/orders/id:' . $this->order->getKey() . '/send-urls',
            )->assertOk();

        $this->assertDatabaseHas('order_products', [
            'id' => $this->digitalProduct->getKey(),
            'is_delivered' => false,
        ]);

        Event::assertNotDispatched(SendOrderUrls::class);
        Mail::assertNotSent(OrderUrls::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderSendUrls($user): void
    {
        $this->{$user}->givePermissionTo('orders.show_details');

        $this->digitalProduct->urls()->create([
            'name' => 'first_url',
            'url' => 'https://example-first-url.com',
        ]);

        Event::fake([SendOrderUrls::class]);
        Mail::fake();

        $this
            ->actingAs($this->{$user})
            ->json(
                'POST',
                '/orders/id:' . $this->order->getKey() . '/send-urls',
            )->assertOk();

        $this->assertDatabaseHas('order_products', [
            'id' => $this->digitalProduct->getKey(),
            'is_delivered' => true,
        ]);

        Event::assertDispatched(SendOrderUrls::class);
    }
}
