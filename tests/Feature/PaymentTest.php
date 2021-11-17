<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\Status;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
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

    public function testPayuUrlUnauthorized(): void
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

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testPayuUrl($user): void
    {
        $this->$user->givePermissionTo('payments.add');

        Http::fakeSequence()
            ->push([
                'access_token' => 'random_access_token',
            ])
            ->push([
                'status' => [
                    'statusCode' => 'SUCCESS',
                ],
                'redirectUri' => 'payment_url',
                'orderId' => 'payu_id',
            ]);

        $code = $this->order->code;
        $response = $this->actingAs($this->$user)
            ->postJson("/orders/$code/pay/payu", [
                'continue_url' => 'continue_url',
            ]);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'method' => 'payu',
                'paid' => false,
                'amount' => $this->order->summary,
                'redirect_url' => 'payment_url',
                'continue_url' => 'continue_url',
            ]);
    }

    public function testPayuNotificationUnauthorized(): void
    {
        $payment = Payment::factory()->make([
            'payed' => false,
        ]);

        $this->order->payments()->save($payment);

        $body = [
            'order' => [
                'status' => 'COMPLETED',
                'extOrderId' => $payment->getKey(),
            ],
        ];
        $signature = md5(json_encode($body) . Config::get('payu.second_key'));

        $response = $this->postJson('/payments/payu', $body, [
            'OpenPayu-Signature' => "signature=$signature;algorithm=MD5"
        ]);

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testPayuNotification($user): void
    {
        $this->$user->givePermissionTo('payments.edit');

        $payment = Payment::factory()->make([
            'payed' => false,
        ]);

        $this->order->payments()->save($payment);

        $body = [
            'order' => [
                'status' => 'COMPLETED',
                'extOrderId' => $payment->getKey(),
            ],
        ];
        $signature = md5(json_encode($body) . Config::get('payu.second_key'));

        $response = $this->actingAs($this->$user)
            ->postJson('/payments/payu', $body, [
                'OpenPayu-Signature' => "signature=$signature;algorithm=MD5"
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('payments', [
            'id' => $payment->getKey(),
            'payed' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOfflinePaymentUnauthorized($user): void
    {
        $code = $this->order->code;
        $response = $this->actingAs($this->$user)
            ->postJson("/orders/$code/pay/offline");

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testOfflinePayment($user): void
    {
        $this->$user->givePermissionTo('payments.offline');

        $code = $this->order->code;
        $response = $this->actingAs($this->$user)
            ->postJson("/orders/$code/pay/offline");

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'method' => 'offline',
                'paid' => true,
                'amount' => $this->order->summary,
                'redirect_url' => null,
                'continue_url' => null,
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->getKey(),
            'method' => 'offline',
            'payed' => true,
            'amount' => $this->order->summary,
        ]);

        $this->order->refresh();
        $this->assertTrue($this->order->isPaid());
    }

    /**
     * @dataProvider authProvider
     */
    public function testOfflinePaymentOverpaid($user): void
    {
        $this->$user->givePermissionTo('payments.offline');

        $amount = $this->order->summary - 1;

        $this->order->payments()->create([
            'method' => 'payu',
            'amount' => 1,
            'payed' => true,
        ]);

        $code = $this->order->code;
        $response = $this->actingAs($this->$user)
            ->postJson("/orders/$code/pay/offline");

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'method' => 'offline',
                'paid' => true,
                'amount' => $amount,
                'redirect_url' => null,
                'continue_url' => null,
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->getKey(),
            'method' => 'offline',
            'payed' => true,
            'amount' => $amount,
        ]);

        $this->order->refresh();
        $this->assertTrue($this->order->isPaid());
    }

//    public function testPayPalNotification(): void
//    {
//        Http::fake();
//
//        $payment = Payment::factory()->make([
//            'payed' => false,
//        ]);
//
//        $this->order->payments()->save($payment);
//
//        $response = $this->post('payments/paypal', [
//            'txn_id' => $payment->external_id,
//            'mc_gross' => number_format($this->order->amount, 2, '.' ,''),
//            'payment_status' => 'Completed',
//        ]);
//
//        $response->assertOk();
//        $this->assertDatabaseHas('payments', [
//            'id' => $payment->getKey(),
//            'payed' => true,
//        ]);
//    }
}
