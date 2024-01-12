<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Mail\OrderCreated;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Status;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Tests\TestCase;
use Tests\Traits\CreateShippingMethod;

final class NewOrderMailTest extends TestCase
{
    use CreateShippingMethod;

    private Order $order;
    private OrderProduct $orderProduct;
    private Product $product;

    /**
     * @throws NumberFormatException
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     */
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
        $mailable = new OrderCreated($this->order);

        $productPrice = $this->orderProduct->price->getAmount(); // 250.20
        $orderSummary = $this->order->summary->getAmount(); // 1261.90
        $shippingPrice = $this->order->shipping_price->getAmount(); // 10.90
        $cartTotal = $this->order->cart_total->getAmount(); // 1251.00

        $mailable->assertSeeInHtml($this->order->code);
        $mailable->assertSeeInHtml($this->order->created_at->format('d-m-Y'));
        $mailable->assertSeeInHtml("{$productPrice} {$this->order->currency->value}");
        $mailable->assertSeeInHtml("{$shippingPrice} {$this->order->currency->value}");
        $mailable->assertSeeInHtml("{$cartTotal} {$this->order->currency->value}");
        $mailable->assertSeeInHtml("{$orderSummary} {$this->order->currency->value}");
        $mailable->assertSeeInHtml($this->product->name);
    }
}
