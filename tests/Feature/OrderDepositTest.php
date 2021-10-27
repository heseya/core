<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Models\Address;
use App\Models\Item;
use App\Models\Option;
use App\Models\Order;
use App\Models\Product;
use App\Models\Schema;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Traits\CreateShippingMethod;

class OrderDepositTest extends TestCase
{
    use CreateShippingMethod;

    private Product $product;
    private Schema $schema;
    private Option $option;
    private Item $item;
    private Address $address;

    private array $request;

    public function setUp(): void
    {
        parent::setUp();

        $this->createShippingMethod();

        Event::fake([OrderCreated::class]);

        $this->product = Product::factory()->create([
            'price' => 100,
            'public' => true,
        ]);
        $this->schema = Schema::factory()->create([
            'type' => 'select',
            'price' => 0,
            'hidden' => false,
        ]);
        $this->product->schemas()->sync([$this->schema->getKey()]);
        $this->option = $this->schema->options()->create([
            'name' => 'XL',
            'price' => 0,
        ]);
        $this->item = Item::factory()->create();
        $this->option->items()->sync([$this->item->getKey()]);
        $this->address = Address::factory()->create();

        $this->request = [
            'email' => 'test@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [
                        $this->schema->getKey() => $this->option->getKey(),
                    ],
                ],
            ],
        ];
    }

    public function testCantCreateOrder(): void
    {
        $this
            ->postJson('/orders', $this->request)
            ->assertUnprocessable();
    }

    public function testCreateOrder(): void
    {
        $this->item->deposits()->create([
            'quantity' => 2,
        ]);

        $response = $this->postJson('/orders', $this->request);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'email' => 'test@example.com',
        ]);
        $this->assertDatabaseHas('addresses', $this->address->toArray());
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->getKey(),
            'product_id' => $this->product->getKey(),
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('order_schemas', [
            'order_product_id' => $order->products->first()->getKey(),
            'name' => $this->schema->name,
            'value' => 'XL',
        ]);
        $this->assertDatabaseHas('deposits', [
            'quantity' => -2,
            'item_id' => $this->item->getKey(),
            'order_product_id' => $order->products->first()->getKey(),
        ]);

        Event::assertDispatched(OrderCreated::class);
    }
}
