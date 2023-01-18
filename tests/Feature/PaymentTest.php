<?php

namespace Tests\Feature;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Events\OrderUpdatedPaid;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\Status;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
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
        $paymentMethod = PaymentMethod::factory()->create([
            'public' => true,
            'name' => 'Payu',
            'alias' => 'payu',
        ]);
        $shipping_method->paymentMethods()->save($paymentMethod);
        $status = Status::factory()->create();
        $product = Product::factory()->create();

        $this->order = Order::factory()->create([
            'shipping_method_id' => $shipping_method->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $this->order->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'price' => 125.50,
            'price_initial' => 125.50,
            'name' => $product->name,
        ]);

        $this->order->refresh();
    }

    public function testPayuUrlUnauthorized(): void
    {
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
        $response = $this->postJson("/orders/{$code}/pay/payu", [
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
            ->postJson("/orders/{$code}/pay/payu", [
                'continue_url' => 'continue_url',
            ]);

        $payment = Payment::find($response->getData()->data->id);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'method' => 'payu',
                'paid' => false,
                'amount' => $this->order->summary,
                'date' => $payment->created_at,
                'redirect_url' => 'payment_url',
                'continue_url' => 'continue_url',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testNotAvailablePaymentMethod($user): void
    {
        $this->$user->givePermissionTo('payments.add');

        $code = $this->order->code;
        $this
            ->actingAs($this->$user)
            ->postJson("/orders/{$code}/pay/przelewy24", [
                'continue_url' => 'continue_url',
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => Exceptions::PAYMENT_METHOD_NOT_AVAILABLE_FOR_SHIPPING,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testNotAvailablePaymentMethodDigital($user): void
    {
        $this->$user->givePermissionTo('payments.add');

        $paymentMethod = PaymentMethod::factory()->create([
            'public' => true,
            'name' => 'Przelewy24',
            'alias' => 'przelewy',
        ]);
        $digitalShippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $digitalShippingMethod->paymentMethods()->save($paymentMethod);

        $this->order->update([
            'digital_shipping_method_id' => $digitalShippingMethod->getKey(),
        ]);

        $code = $this->order->code;
        $this
            ->actingAs($this->$user)
            ->postJson("/orders/{$code}/pay/przelewy24", [
                'continue_url' => 'continue_url',
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => Exceptions::PAYMENT_METHOD_NOT_AVAILABLE_FOR_SHIPPING,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testPaymentMethodDigital($user): void
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

        $digitalShippingMethod = ShippingMethod::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create([
            'public' => true,
            'name' => 'Payu',
            'alias' => 'payu',
        ]);
        $digitalShippingMethod->paymentMethods()->save($paymentMethod);

        $this->order->shippingMethod()->dissociate();
        $this->order->digitalShippingMethod()->associate($digitalShippingMethod);

        $response = $this->actingAs($this->$user)
            ->postJson("/orders/{$code}/pay/payu", [
                'continue_url' => 'continue_url',
            ]);

        $payment = Payment::find($response->getData()->data->id);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'method' => 'payu',
                'paid' => false,
                'amount' => $this->order->summary,
                'date' => $payment->created_at,
                'redirect_url' => 'payment_url',
                'continue_url' => 'continue_url',
            ]);
    }

    public function testPayuNotificationUnauthorized(): void
    {
        $payment = Payment::factory()->make([
            'paid' => false,
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
            'OpenPayu-Signature' => "signature={$signature};algorithm=MD5",
        ]);

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testPayuNotification($user): void
    {
        Event::fake(OrderUpdatedPaid::class);

        $this->$user->givePermissionTo('payments.edit');

        $payment = Payment::factory()->make([
            'paid' => false,
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
                'OpenPayu-Signature' => "signature={$signature};algorithm=MD5",
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('payments', [
            'id' => $payment->getKey(),
            'paid' => true,
        ]);

        Event::assertDispatched(OrderUpdatedPaid::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOfflinePaymentUnauthorized($user): void
    {
        $code = $this->order->code;
        $response = $this->actingAs($this->$user)
            ->postJson("/orders/{$code}/pay/offline");

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testOfflinePayment($user): void
    {
        Event::fake(OrderUpdatedPaid::class);

        $this->$user->givePermissionTo('payments.offline');

        $code = $this->order->code;
        $response = $this->actingAs($this->$user)
            ->postJson("/orders/{$code}/pay/offline");

        $payment = Payment::find($response->getData()->data->id);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'method' => 'offline',
                'paid' => true,
                'amount' => $this->order->summary,
                'date' => $payment->created_at,
                'redirect_url' => null,
                'continue_url' => null,
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->getKey(),
            'method' => 'offline',
            'paid' => true,
            'amount' => $this->order->summary,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'paid' => true,
        ]);

        $this->order->refresh();
        $this->assertTrue($this->order->paid);

        Event::assertDispatched(OrderUpdatedPaid::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testPaymentHasDate($user): void
    {
        $this->$user->givePermissionTo('payments.offline');

        $code = $this->order->code;
        $response = $this->actingAs($this->$user)
            ->postJson("/orders/{$code}/pay/offline");

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'date' => Payment::find($response->getData()->data->id)->created_at,
            ]);
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
            'paid' => true,
        ]);

        $code = $this->order->code;
        $response = $this->actingAs($this->$user)
            ->postJson("/orders/{$code}/pay/offline");

        $payment = Payment::find($response->getData()->data->id);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'method' => 'offline',
                'paid' => true,
                'amount' => $amount,
                'date' => $payment->created_at,
                'redirect_url' => null,
                'continue_url' => null,
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->getKey(),
            'method' => 'offline',
            'paid' => true,
            'amount' => $amount,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'paid' => true,
        ]);

        $this->order->refresh();
        $this->assertTrue($this->order->paid);
    }

//    public function testPayPalNotification(): void
//    {
//        Http::fake();
//
//        $payment = Payment::factory()->make([
//            'paid' => false,
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
//            'paid' => true,
//        ]);
//    }
}
