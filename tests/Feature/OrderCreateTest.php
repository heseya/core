<?php

namespace Tests\Feature;

use App\Events\OrderCreated;
use App\Models\Address;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Schema;
use App\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderCreateTest extends TestCase
{
    use RefreshDatabase;

    private ShippingMethod $shippingMethod;
    private Category $category;
    private Brand $brand;
    private Address $address;
    private Product $product;

    public function setUp(): void
    {
        parent::setUp();

        $this->shippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $this->category = Category::factory()->create(['public' => true]);
        $this->brand = Brand::factory()->create(['public' => true]);
        $this->address = Address::factory()->make();

        $this->product = Product::factory()->create([
            'category_id' => $this->category->getKey(),
            'brand_id' => $this->brand->getKey(),
            'public' => true,
        ]);
    }

    public function testCreateSimpleOrder(): void
    {
        Event::fake([OrderCreated::class]);

        $this->product->update([
            'price' => 10,
        ]);

        $response = $this->postJson('/orders', [
            'email' => 'test@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 20,
                ],
            ],
        ]);

        dd($response->json());

        $response->assertCreated();
        $order = $response->getData()->data;

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => 'test@example.com',
        ]);
        $this->assertDatabaseHas('addresses', $this->address->toArray());
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $this->product->getKey(),
            'quantity' => 20,
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    public function testCreateOrder(): void
    {
        Event::fake([OrderCreated::class]);

        $schema = Schema::factory()->create([
            'type' => 'string',
            'price' => 10,
        ]);

        $this->product->schemas()->attach($schema);
        $this->product->update([
            'price' => 100,
        ]);

        $response = $this->postJson('/orders', [
            'email' => 'test@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [
                        $schema->getKey() => 'Test',
                    ]
                ],
            ],
        ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => 'test@example.com',
        ]);
        $this->assertDatabaseHas('addresses', $this->address->toArray());
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->getKey(),
            'product_id' => $this->product->getKey(),
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('order_schemas', [
            'order_product_id' => $order->products[0]->getKey(),
            'name' => $schema->name,
            'value' => 'Test',
        ]);

        Event::assertDispatched(OrderCreated::class);
    }
}
