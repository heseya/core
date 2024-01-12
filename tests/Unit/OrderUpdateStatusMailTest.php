<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Mail\OrderStatusUpdated;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\Status;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Language\Language;
use Tests\TestCase;
use Tests\Traits\CreateShippingMethod;

final class OrderUpdateStatusMailTest extends TestCase
{
    use CreateShippingMethod;

    private Order $order;
    private Status $status;

    public function setUp(): void
    {
        parent::setUp();

        $currency = Currency::DEFAULT;

        Product::factory()->create();

        $shippingMethod = $this->createShippingMethod(10);
        $this->status = Status::factory()->create([
            'name' => 'Nowy status',
        ]);
        $address = Address::factory()->create([
            'country' => 'en',
        ]);

        $this->order = Order::factory()->create([
            'email' => 'test@example.com',
            'shipping_method_id' => $shippingMethod->getKey(),
            'shipping_price_initial' => Money::of(10.9, $currency->value),
            'shipping_price' => Money::of(10.9, $currency->value),
            'cart_total_initial' => Money::of(1251, $currency->value),
            'cart_total' => Money::of(1251, $currency->value),
            'summary' => Money::of(1261.9, $currency->value),
            'status_id' => $this->status->getKey(),
            'currency' => 'PLN',
            'shipping_address_id' => $address->getKey(),
            'language' => 'en',
        ]);
    }

    public function testMailContentDefaultFallback(): void
    {
        (new OrderStatusUpdated($this->order))->assertSeeInHtml('Nowy status');
    }
}
