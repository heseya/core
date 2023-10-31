<?php

namespace Tests\Unit;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\Status;
use App\Notifications\OrderStatusUpdated;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Language\Language;
use Tests\TestCase;
use Tests\Traits\CreateShippingMethod;

class OrderUpdateStatusMailTest extends TestCase
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
        ]);
    }

    public function testMailContentDefaultFallback(): void
    {
        $notification = new OrderStatusUpdated($this->order);
        $rendered = $notification->toMail($this->order)->render();

        $this->assertStringContainsString("Nowy status", $rendered);
    }

    public function testMailContentEn(): void
    {
        $en = Language::firstOrCreate([
            'iso' => 'en',
        ], [
            'name' => 'English',
            'default' => false,
        ]);

        $this->status->setLocale($en->getKey())->fill(['name' => 'New status']);
        $this->status->update([
            'published' => [$this->lang, $en->getKey()],
        ]);
        $this->status->save();

        $notification = new OrderStatusUpdated($this->order);
        $rendered = $notification->toMail($this->order)->render();

        $this->assertStringContainsString("New status", $rendered);
    }
}
