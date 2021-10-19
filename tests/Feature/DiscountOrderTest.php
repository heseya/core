<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreateProduct;
use Tests\Traits\CreateShippingMethod;

class DiscountOrderTest extends TestCase
{
    use CreateProduct, CreateShippingMethod;

    protected Product $product;
    protected ShippingMethod $shippingMethod;

    protected array $items;
    protected array $address;

    public function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->product = $this->createProduct([
            'price' => 100,
        ]);

        $this->shippingMethod = $this->createShippingMethod(10);

        $this->items = [[
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
        ]];

        $this->address = [
            'name' => 'Test User',
            'address' => 'Gdańska 89/1',
            'zip' => '85-022',
            'city' => 'Bydgoszcz',
            'phone' => '+48123123123',
            'country' => 'PL',
        ];
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderCreate($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'type' => DiscountType::PERCENTAGE,
            'discount' => 15,
        ]);

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address,
            'items' => $this->items,
            'discounts' => [
                $discount->code,
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment(['summary' => 95]); // 100 - 100 * 15% + 10 (delivery)

        $orderId = $response->getData()->data->id;

        $this->assertDatabaseHas('order_discounts', [
            'order_id' => $orderId,
            'discount_id' => $discount->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderCreateAmountDiscount($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'type' => DiscountType::AMOUNT,
            'discount' => 50,
        ]);

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address,
            'items' => $this->items,
            'discounts' => [
                $discount->code,
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment(['summary' => 60]); // 100 - 50 + 10 (delivery)
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderCreateChangeDiscount($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'type' => DiscountType::PERCENTAGE,
            'discount' => 10,
        ]);

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'info@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'delivery_address' => $this->address,
            'items' => $this->items,
            'discounts' => [
                $discount->code,
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment(['summary' => 100]); // 100 - 100 * 10% + 10 (delivery)

        $orderId = $response->getData()->data->id;

        $discount->update([
            'type' => DiscountType::AMOUNT,
            'discount' => 100,
        ]);

        $order = Order::find($orderId);
        $this->assertEquals(100, $order->summary);
    }
}
