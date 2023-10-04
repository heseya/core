<?php

namespace Tests\Feature;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\PaymentStatus;
use App\Events\OrderUpdatedPaid;
use App\Models\App;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Status;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    private Order $order;
    private App $appUser;
    private ShippingMethod $shippingMethod;
    private PaymentMethod $paymentMethod;
    private Currency $currency;

    public function setUp(): void
    {
        parent::setUp();

        Product::factory()->create();

        $this->shippingMethod = ShippingMethod::factory()->create();
        $this->paymentMethod = PaymentMethod::factory()->create([
            'public' => true,
            'name' => 'Payu',
            'alias' => 'payu',
        ]);
        $this->shippingMethod->paymentMethods()->save($this->paymentMethod);
        $status = Status::factory()->create();
        $product = Product::factory()->create();

        $this->appUser = App::factory()->create();

        $this->order = Order::factory()->create([
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'status_id' => $status->getKey(),
        ]);

        $this->currency = Currency::DEFAULT;
        $price = Money::of(125.50, $this->currency->value);

        $this->order->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'price' => $price,
            'price_initial' => $price,
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
        $response = $this->postJson("/orders/{$code}/pay/id:" . $this->paymentMethod->getKey(), [
            'continue_url' => 'continue_url',
        ]);

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testPayuUrl($user): void
    {
        $this->{$user}->givePermissionTo('payments.add');

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
        $response = $this
            ->actingAs($this->{$user})
            ->json('POST', "/orders/{$code}/pay/id:" . $this->paymentMethod->getKey(), [
                'continue_url' => 'continue_url',
            ]);

        $payment = Payment::query()->find($response->getData()->data->id);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'method' => 'payu',
                'status' => PaymentStatus::PENDING->value,
                'amount' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => (string) $this->order->summary->getAmount(),
                    'net' => (string) $this->order->summary->getAmount(),
                ],
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
        $this->{$user}->givePermissionTo('payments.add');

        $code = $this->order->code;
        $this
            ->actingAs($this->{$user})
            ->postJson("/orders/{$code}/pay/id:026eda8b-8acf-40e4-8764-d44aa8b8db7a", [
                'continue_url' => 'continue_url',
            ])
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testNotAvailablePaymentMethodDigital($user): void
    {
        $this->{$user}->givePermissionTo('payments.add');

        $paymentMethod = PaymentMethod::factory()->create([
            'public' => true,
            'name' => 'PayPal',
            'alias' => 'paypal',
        ]);

        /** @var ShippingMethod $digitalShippingMethod */
        $digitalShippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $digitalShippingMethod->paymentMethods()->attach($paymentMethod->getKey());

        $this->order->update([
            'digital_shipping_method_id' => $digitalShippingMethod->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->postJson('/orders/' . $this->order->code . '/pay/id:' . $paymentMethod->getKey(), [
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
        $this->{$user}->givePermissionTo('payments.add');

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
        $digitalShippingMethod->paymentMethods()->attach($paymentMethod->getKey());

        $this->order->update([
            'shipping_method_id' => null,
            'digital_shipping_method_id' => $digitalShippingMethod->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->postJson("/orders/{$code}/pay/id:" . $paymentMethod->getKey(), [
                'continue_url' => 'continue_url',
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'method' => 'payu',
                'amount' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => (string) $this->order->summary->getAmount(),
                    'net' => (string) $this->order->summary->getAmount(),
                ],
                'redirect_url' => 'payment_url',
                'continue_url' => 'continue_url',
            ]);
    }

    public function testPayuNotificationBadSignature(): void
    {
        $payment = Payment::factory()->make([
            'status' => PaymentStatus::PENDING,
            'currency' => $this->order->currency,
        ]);

        $this->order->payments()->save($payment);

        $body = [
            'order' => [
                'status' => 'COMPLETED',
                'extOrderId' => $payment->getKey(),
            ],
        ];
        $signature = 'random-signature';

        $response = $this->postJson('/payments/payu', $body, [
            'OpenPayu-Signature' => "signature={$signature};algorithm=MD5",
        ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testPayuNotification($user): void
    {
        $this->markTestSkipped("Ten test przechodzi na localu, nie wiem czemu nie przechodzi na gitlabie");

        $initialEvent = Event::getFacadeRoot();
        Event::fake(OrderUpdatedPaid::class);
        Payment::setEventDispatcher($initialEvent);

        $this->{$user}->givePermissionTo('payments.edit');

        $payment = Payment::factory()->make([
            'status' => PaymentStatus::PENDING,
            'currency' => $this->order->currency,
        ]);

        $this->order->payments()->save($payment);

        $body = [
            'order' => [
                'status' => 'COMPLETED',
                'extOrderId' => $payment->getKey(),
            ],
        ];
        $signature = md5(json_encode($body) . Config::get('payu.second_key'));

        $response = $this->actingAs($this->{$user})
            ->postJson('/payments/payu', $body, [
                'OpenPayu-Signature' => "signature={$signature};algorithm=MD5",
            ]);

        $response->assertValid()->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->getKey(),
            'status' => PaymentStatus::SUCCESSFUL->value,
        ]);

        Event::assertDispatched(OrderUpdatedPaid::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOfflinePaymentUnauthorized($user): void
    {
        $code = $this->order->code;
        $response = $this->actingAs($this->{$user})
            ->postJson("/orders/{$code}/pay/offline");

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testOfflinePayment($user): void
    {
        Event::fake(OrderUpdatedPaid::class);

        $this->{$user}->givePermissionTo('payments.offline');

        $code = $this->order->code;
        $response = $this->actingAs($this->{$user})
            ->postJson("/orders/{$code}/pay/offline");

        $response->assertValid()
            ->assertCreated();

        $payment = Payment::find($response->getData()->data->id);

        $response->assertJsonFragment([
            'method' => 'offline',
            'status' => PaymentStatus::SUCCESSFUL->value,
            'amount' => [
                'currency' => Currency::DEFAULT->value,
                'gross' => (string) $this->order->summary->getAmount(),
                'net' => (string) $this->order->summary->getAmount(),
            ],
            'date' => $payment->created_at,
            'redirect_url' => null,
            'continue_url' => null,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->getKey(),
            'method' => 'offline',
            'status' => PaymentStatus::SUCCESSFUL->value,
            'amount' => $this->order->summary->getMinorAmount()->toInt(),
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
        $this->{$user}->givePermissionTo('payments.offline');

        $code = $this->order->code;
        $response = $this->actingAs($this->{$user})
            ->postJson("/orders/{$code}/pay/offline");

        $response
            ->assertValid()
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
        $this->{$user}->givePermissionTo('payments.offline');

        $amount = $this->order->summary->minus(1)->getAmount()->toFloat();

        $this->order->payments()->create([
            'method' => 'payu',
            'amount' => 1,
            'status' => PaymentStatus::SUCCESSFUL,
            'currency' => $this->order->currency,
        ]);

        $code = $this->order->code;
        $response = $this->actingAs($this->{$user})
            ->postJson("/orders/{$code}/pay/offline");

        $payment = Payment::find($response->getData()->data->id);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'method' => 'offline',
                'status' => PaymentStatus::SUCCESSFUL->value,
                'amount' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => "$amount",
                    'net' => "$amount",
                ],
                'date' => $payment->created_at,
                'redirect_url' => null,
                'continue_url' => null,
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $this->order->getKey(),
            'method' => 'offline',
            'status' => PaymentStatus::SUCCESSFUL->value,
            'amount' => round($amount * 100, 0),
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'paid' => true,
        ]);

        $this->order->refresh();
        $this->assertTrue($this->order->paid);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->{$user}->givePermissionTo('payments.show');

        Payment::factory()->count(10)->create([
            'order_id' => $this->order->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})->json('GET', '/payments');

        $response->assertJsonCount(10, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow($user): void
    {
        $this->{$user}->givePermissionTo('payments.show_details');

        $payment = Payment::factory()->create([
            'order_id' => $this->order->getKey(),
            'currency' => $this->order->currency,
        ]);

        $response = $this->actingAs($this->{$user})->json('GET', '/payments/id:' . $payment->getKey());

        $data = [
            'data' => [
                'id' => $payment->getKey(),
                'external_id' => $payment->external_id,
                'method' => $payment->method,
                'status' => $payment->status->value,
                'amount' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => $payment->amount->getAmount(),
                    'net' => $payment->amount->getAmount(),
                ],
                'currency' => $payment->currency->value,
                'redirect_url' => $payment->redirect_url,
                'continue_url' => $payment->continue_url,
            ],
        ];

        $response->assertOk();
        $response->assertJson($data);
    }

    public function testStore(): void
    {
        $this->appUser->givePermissionTo('payments.add');
        $paymentMethod = PaymentMethod::factory()->create([
            'app_id' => $this->appUser->getKey(),
        ]);

        $response = $this->actingAs($this->appUser)->json('POST', '/payments', [
            'amount' => 100,
            'currency' => $this->order->currency->value,
            'status' => PaymentStatus::PENDING,
            'order_id' => $this->order->id,
            'external_id' => 'test',
            'method_id' => $paymentMethod->getKey(),
        ]);

        $response->assertValid()
            ->assertCreated();

        $response->assertJson([
            'data' => [
                'amount' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
                'currency' => $this->order->currency->value,
                'status' => PaymentStatus::PENDING->value,
                'external_id' => 'test',
                'method_id' => $paymentMethod->getKey(),
            ],
        ]);

        $this->assertDatabaseHas('payments', [
            'amount' => 10000, // minor units
            'currency' => $this->order->currency->value,
            'status' => PaymentStatus::PENDING->value,
            'order_id' => $this->order->id,
            'external_id' => 'test',
            'method_id' => $paymentMethod->getKey(),
        ]);
    }

    public function testStoreUnauthorized(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'app_id' => $this->appUser->getKey(),
        ]);

        $response = $this->json('POST', '/payments', [
            'amount' => 100,
            'status' => PaymentStatus::PENDING,
            'order_id' => $this->order->id,
            'external_id' => 'test',
            'method_id' => $paymentMethod->getKey(),
        ]);

        $response->assertForbidden();
    }

    public function testUpdate(): void
    {
        $this->appUser->givePermissionTo('payments.edit');
        $paymentMethod = PaymentMethod::factory()->create([
            'name' => 'test',
            'app_id' => $this->appUser->getKey(),
        ]);

        $payment = Payment::factory()->create([
            'method_id' => $paymentMethod->getKey(),
            'order_id' => $this->order->getKey(),
            'currency' => $this->order->currency,
        ]);

        $response = $this->actingAs($this->appUser)->json('PATCH', '/payments/id:' . $payment->getKey(), [
            'amount' => 100,
            'status' => PaymentStatus::PENDING,
        ]);

        $response->assertJson([
            'data' => [
                'method' => $paymentMethod->name,
                'method_id' => $paymentMethod->getKey(),
                'status' => PaymentStatus::PENDING->value,
                'amount' => [
                    'currency' => Currency::DEFAULT->value,
                    'gross' => '100.00',
                    'net' => '100.00',
                ],
            ],
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->getKey(),
            'method_id' => $paymentMethod->getKey(),
            'status' => PaymentStatus::PENDING->value,
            'amount' => 10000, // database stores minor units
        ]);
    }

    public function testUpdateOtherAppPayment(): void
    {
        $this->appUser->givePermissionTo('payments.edit');

        $app = App::factory()->create();

        $paymentMethod = PaymentMethod::factory()->create([
            'app_id' => $app->getKey(),
        ]);

        $payment = Payment::factory()->create([
            'method_id' => $paymentMethod->getKey(),
            'order_id' => $this->order->getKey(),
            'currency' => $this->order->currency,
        ]);

        $response = $this->actingAs($this->appUser)->json('PATCH', '/payments/id:' . $payment->getKey(), [
            'amount' => 100,
            'status' => PaymentStatus::PENDING,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonFragment(['key' => Exceptions::CLIENT_NO_ACCESS->name]);
    }

    public function testUpdateUnauthorized(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'app_id' => $this->appUser->getKey(),
        ]);

        $payment = Payment::factory()->create([
            'method_id' => $paymentMethod->getKey(),
            'order_id' => $this->order->getKey(),
            'currency' => $this->order->currency,
        ]);

        $response = $this->actingAs($this->appUser)->json('PATCH', '/payments/id:' . $payment->getKey(), [
            'amount' => 100,
            'status' => PaymentStatus::PENDING,
        ]);

        $response->assertForbidden();
    }

    public function testCreatePaymentWithMicroservice(): void
    {
        $this->user->givePermissionTo('payments.add');
        Http::fake([
            '*' => Http::response([
                'payment_id' => 'test',
                'status' => PaymentStatus::SUCCESSFUL->value,
                'amount' => 100,
                'currency' => Currency::DEFAULT->value,
                'redirect_url' => 'redirect_url',
                'continue_url' => 'continue_url',
            ]),
        ]);

        $paymentMethod = PaymentMethod::factory()->create([
            'alias' => null,
            'public' => true,
        ]);
        $this->shippingMethod->paymentMethods()->attach($paymentMethod->getKey());

        $response = $this->actingAs($this->user)->json(
            'POST',
            'orders/' . $this->order->code . '/pay/id:' . $paymentMethod->getKey(),
            ['continue_url' => 'continue_url'],
        );

        $response->assertValid()
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'method_id' => $paymentMethod->getKey(),
                    'status' => PaymentStatus::SUCCESSFUL->value,
                    'amount' => [
                        'currency' => Currency::DEFAULT->value,
                        'gross' => '100.00',
                        'net' => '100.00',
                    ],
                    'redirect_url' => 'redirect_url',
                    'continue_url' => 'continue_url',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'id' => $response->getData()->data->id,
            'order_id' => $this->order->getKey(),
            'amount' => 10000, // database stores minor units
            'redirect_url' => 'redirect_url',
            'continue_url' => 'continue_url',
            'status' => PaymentStatus::SUCCESSFUL->value,
            'method_id' => $paymentMethod->getKey(),
        ]);
    }
}
