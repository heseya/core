<?php

namespace Unit;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Status;
use App\Notifications\OrderCreated;
use Tests\TestCase;
use Tests\Traits\CreateShippingMethod;

class NewOrderMailTest extends TestCase
{
    use CreateShippingMethod;

    private Order $order;
    private OrderProduct $orderProduct;

    public function setUp(): void
    {
        parent::setUp();

        Product::factory()->create();

        $this->shippingMethod = $this->createShippingMethod(10);
        $status = Status::factory()->create();
        $product = Product::factory()->create();

        $this->order = Order::factory()->create([
            'email' => 'test@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_price_initial' => 10.9,
            'shipping_price' => 10.9,
            'cart_total_initial' => 1251,
            'cart_total' => 1251,
            'summary' => 1261.9,
            'status_id' => $status->getKey(),
        ]);

        $this->orderProduct = $this->order->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 5,
            'price' => 250.2,
            'price_initial' => 250.2,
            'name' => $product->name,
        ]);
    }

    public function testMailContent(): void
    {
        $notification = new OrderCreated($this->order);
        $rendered = $notification->toMail($this->order)->render();

        $orderCode = $this->order->code;
        $productPrice = number_format($this->orderProduct->price, 2, '.', ''); // 250.20
        $orderSummary = number_format($this->order->summary, 2, '.', ''); // 1261.90
        $shippingPrice = number_format($this->order->shipping_price, 2, '.', ''); // 10.90
        $cartTotal = number_format($this->order->cart_total, 2, '.', ''); // 1251

        $this->assertStringContainsString("Order <b>${orderCode}</b> Confirmation", $rendered);
        $this->assertStringContainsString(">${productPrice} PLN</td>", $rendered);
        $this->assertStringContainsString("Delivery price: <b>${shippingPrice} PLN</b>", $rendered);
        $this->assertStringContainsString("Cart: <b>${cartTotal} PLN</b>", $rendered);
        $this->assertStringContainsString("Summary: <b>${orderSummary} PLN</b>", $rendered);
    }
}
