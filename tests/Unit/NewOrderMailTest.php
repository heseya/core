<?php

namespace Unit;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Status;
use App\Notifications\OrderCreated;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Language\Language;
use Tests\TestCase;
use Tests\Traits\CreateShippingMethod;

class NewOrderMailTest extends TestCase
{
    use CreateShippingMethod;

    private Order $order;
    private OrderProduct $orderProduct;
    private Product $product;

    public function setUp(): void
    {
        parent::setUp();

        $currency = Currency::DEFAULT;

        Product::factory()->create();

        $this->shippingMethod = $this->createShippingMethod(10);
        $status = Status::factory()->create();
        $this->product = Product::factory()->create();

        $this->order = Order::factory()->create([
            'email' => 'test@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_price_initial' => Money::of(10.9, $currency->value),
            'shipping_price' => Money::of(10.9, $currency->value),
            'cart_total_initial' => Money::of(1251, $currency->value),
            'cart_total' => Money::of(1251, $currency->value),
            'summary' => Money::of(1261.9, $currency->value),
            'status_id' => $status->getKey(),
            'currency' => 'PLN',
            'language' => 'en',
        ]);

        $this->orderProduct = $this->order->products()->create([
            'product_id' => $this->product->getKey(),
            'quantity' => 5,
            'price' => Money::of(250.2, $currency->value),
            'price_initial' => Money::of(250.2, $currency->value),
            'name' => $this->product->name,
        ]);
    }

    public function testMailContent(): void
    {
        $notification = new OrderCreated($this->order);
        $rendered = $notification->toMail($this->order)->render();

        $orderCode = $this->order->code;
        $date = $this->order->created_at->format('d-m-Y');
        $productPrice = $this->orderProduct->price->getAmount(); // 250.20
        $orderSummary = $this->order->summary->getAmount(); // 1261.90
        $shippingPrice = $this->order->shipping_price->getAmount(); // 10.90
        $cartTotal = $this->order->cart_total->getAmount(); // 1251.00
        $productName = $this->product->name;

        $this->assertStringContainsString("{$orderCode}", $rendered);
        $this->assertStringContainsString("{$date}", $rendered);
        $this->assertStringContainsString("{$productPrice} {$this->order->currency->value}</td>", $rendered);
        $this->assertStringContainsString("{$shippingPrice} {$this->order->currency->value}</b>", $rendered);
        $this->assertStringContainsString("{$cartTotal} {$this->order->currency->value}</b>", $rendered);
        $this->assertStringContainsString("{$orderSummary} {$this->order->currency->value}</b>", $rendered);
        $this->assertStringContainsString("{$productName}", $rendered);
    }

    public function testMailContentEn(): void
    {
        Language::firstOrCreate([
            'iso' => 'en',
        ], [
            'name' => 'English',
            'default' => false,
        ]);

        $this->orderProduct->update([
            'name' => 'English name',
        ]);

        $notification = new OrderCreated($this->order);
        $rendered = $notification->toMail($this->order)->render();

        $this->assertStringContainsString("English name", $rendered);
    }
}
