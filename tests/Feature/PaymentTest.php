<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Events\OrderStatusUpdated;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\Status;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    private Order $order;

    public function setUp(): void
    {
        parent::setUp();

        Product::factory()->create();

        $shipping_method = ShippingMethod::factory()->create();
        $status = Status::factory()->create();
        $product = Product::factory()->create();

        $this->order = Order::factory()->create([
            'shipping_method_id' => $shipping_method->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $item = $this->order->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'price' => 125.50,
        ]);
    }

    public function testPayuUrl(): void
    {
        Http::fakeSequence()
            ->push([
                'access_token' => 'random_access_token',
            ], 200)
            ->push([
                'status' => [
                    'statusCode' => 'SUCCESS',
                ],
                'redirectUri' => 'payment_url',
                'orderId' => 'payu_id',
            ], 200);

        $code = $this->order->code;
        $response = $this->postJson("/orders/$code/pay/payu", [
            'continue_url' => 'continue_url',
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'external_id' => 'payu_id',
                'method' => 'payu',
                'payed' => false,
                'amount' => $this->order->summary,
                'redirect_url' => 'payment_url',
                'continue_url' => 'continue_url',
            ]);
    }

    public function testPayuNotification(): void
    {
        $payment = Payment::factory()->make([
            'payed' => false,
        ]);
        
        $this->order->payments()->save($payment);

        $response = $this->postJson("payments/payu", [
            'order' => [
                'status' => 'COMPLETED',
                'extOrderId' => $payment->getKey(),
            ],
        ]);

        $payment->refresh();

        $response->assertOk();
        $this->assertTrue($payment->payed);
    }
}
