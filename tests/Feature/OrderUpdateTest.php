<?php

namespace Tests\Feature;

use App\Enums\ShippingType;
use App\Events\OrderRequestedShipping;
use App\Events\OrderUpdated;
use App\Events\OrderUpdatedShippingNumber;
use App\Listeners\WebHookEventListener;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PackageTemplate;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Models\WebHook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;
use Tests\Traits\CreateShippingMethod;

class OrderUpdateTest extends TestCase
{
    use RefreshDatabase, WithFaker, CreateShippingMethod;

    public const EMAIL = 'test@example.com';
    private Order $order;
    private string $comment;
    private Status $status;
    private Address $addressDelivery;
    private Address $addressInvoice;
    private Address $address;

    public function setUp(): void
    {
        parent::setUp();

        $shippingMethod = $this->createShippingMethod(0, ['shipping_type' => ShippingType::ADDRESS]);
        $this->comment = $this->faker->text(10);
        $this->status = Status::factory()->create();
        $this->addressDelivery = Address::factory()->create();
        $this->addressInvoice = Address::factory()->create();
        $this->address = Address::factory()->make();

        $this->order = Order::factory()->create([
            'code' => 'XXXXXX123',
            'email' => self::EMAIL,
            'comment' => $this->comment,
            'status_id' => $this->status->getKey(),
            'shipping_method_id' => $shippingMethod->getKey(),
            'billing_address_id' => $this->addressInvoice->getKey(),
            'shipping_address_id' => $this->addressDelivery->getKey(),
        ]);

        Product::factory()->create([
            'public' => true,
        ]);

        $this->order->products()->save(
            OrderProduct::factory()->make(),
        );
    }

    public function testUpdateUnauthorized(): void
    {
        Event::fake();

        $response = $this->patchJson('/orders/id:' . $this->order->id);
        $response->assertForbidden();

        Event::assertNotDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testFullUpdateOrder($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $email = $this->faker->email();
        $comment = $this->faker->text(200);
        $address = Address::factory()->create();

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'email' => $email,
            'comment' => $comment,
            'shipping_place' => $address->toArray(),
            'billing_address' => $address->toArray(),
        ]);

        $responseData = $response->getData()->data;

        $response
            ->assertOk()
            ->assertJsonFragment([
                'code' => $this->order->code,
                'status' => [
                    'cancel' => false,
                    'color' => $this->status->color,
                    'description' => $this->status->description,
                    'id' => $this->status->id,
                    'name' => $this->status->name,
                    'hidden' => $this->status->hidden,
                    'no_notifications' => $this->status->no_notifications,
                    'metadata' => [],
                ],
                'shipping_place' => [
                    'id' => $responseData->shipping_place->id,
                    'address' => $address->address,
                    'city' => $address->city,
                    'country' => $address->country ?? null,
                    'country_name' => $responseData->shipping_place->country_name,
                    'name' => $address->name,
                    'phone' => $address->phone,
                    'vat' => $address->vat,
                    'zip' => $address->zip,
                ],
                'billing_address' => [
                    'id' => $responseData->billing_address->id,
                    'address' => $address->address,
                    'city' => $address->city,
                    'country' => $address->country,
                    'country_name' => $responseData->billing_address->country_name,
                    'name' => $address->name,
                    'phone' => $address->phone,
                    'vat' => $address->vat,
                    'zip' => $address->zip,
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'email' => $email,
            'comment' => $comment,
            'shipping_address_id' => $responseData->shipping_place->id,
            'billing_address_id' => $responseData->billing_address->id,
        ]);

        Event::assertDispatched(OrderUpdated::class);

        Queue::fake();

        $order = Order::find($this->order->getKey());
        $event = new OrderUpdated($order);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testFullUpdateOrderWithPartialAddresses($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $email = $this->faker->email();
        $comment = $this->faker->text(200);

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'email' => $email,
            'comment' => $comment,
            'shipping_place' => [
                'name' => 'delivery test',
            ],
            'billing_address' => [
                'name' => 'invoice test',
            ],
        ]);

        $responseData = $response->getData()->data;
        $response
            ->assertOk()
            ->assertJsonFragment([
                'shipping_place' => [
                    'id' => $this->addressDelivery->getKey(),
                    'address' => $this->addressDelivery->address,
                    'city' => $this->addressDelivery->city,
                    'country' => $this->addressDelivery->country,
                    'country_name' => $responseData->shipping_place->country_name,
                    'name' => 'delivery test',
                    'phone' => $this->addressDelivery->phone,
                    'vat' => $this->addressDelivery->vat,
                    'zip' => $this->addressDelivery->zip,
                ],
                'billing_address' => [
                    'id' => $this->addressInvoice->getKey(),
                    'address' => $this->addressInvoice->address,
                    'city' => $this->addressInvoice->city,
                    'country' => $this->addressInvoice->country,
                    'country_name' => $responseData->billing_address->country_name,
                    'name' => 'invoice test',
                    'phone' => $this->addressInvoice->phone,
                    'vat' => $this->addressInvoice->vat,
                    'zip' => $this->addressInvoice->zip,
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'email' => $email,
            'comment' => $comment,
            'shipping_address_id' => $responseData->shipping_place->id,
            'billing_address_id' => $responseData->billing_address->id,
        ])
            ->assertDatabaseHas('addresses', [
                'id' => $this->addressDelivery->getKey(),
                'address' => $this->addressDelivery->address,
                'city' => $this->addressDelivery->city,
                'country' => $this->addressDelivery->country,
                'name' => 'delivery test',
                'phone' => $this->addressDelivery->phone,
                'vat' => $this->addressDelivery->vat,
                'zip' => $this->addressDelivery->zip,
            ])
            ->assertDatabaseHas('addresses', [
                'id' => $this->addressInvoice->getKey(),
                'address' => $this->addressInvoice->address,
                'city' => $this->addressInvoice->city,
                'country' => $this->addressInvoice->country,
                'name' => 'invoice test',
                'phone' => $this->addressInvoice->phone,
                'vat' => $this->addressInvoice->vat,
                'zip' => $this->addressInvoice->zip,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testFullUpdateOrderWithWebHookQueue($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        WebHook::factory()->create([
            'events' => [
                'OrderUpdated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake([OrderUpdated::class]);

        $email = $this->faker->freeEmail;
        $comment = $this->faker->text(200);
        $address = Address::factory()->create();

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'email' => $email,
            'comment' => $comment,
            'billing_address' => $address->toArray(),
            'shipping_place' => $address->toArray(),
        ]);

        $responseData = $response->getData()->data;
        $response
            ->assertOk()
            ->assertJsonFragment([
                'code' => $this->order->code,
                'status' => [
                    'cancel' => false,
                    'color' => $this->status->color,
                    'description' => $this->status->description,
                    'id' => $this->status->id,
                    'name' => $this->status->name,
                    'hidden' => $this->status->hidden,
                    'no_notifications' => $this->status->no_notifications,
                    'metadata' => [],
                ],
                'shipping_place' => [
                    'id' => $responseData->shipping_place->id,
                    'address' => $address->address,
                    'city' => $address->city,
                    'country' => $address->country ?? null,
                    'country_name' => $responseData->shipping_place->country_name,
                    'name' => $address->name,
                    'phone' => $address->phone,
                    'vat' => $address->vat,
                    'zip' => $address->zip,
                ],
                'billing_address' => [
                    'id' => $responseData->billing_address->id,
                    'address' => $address->address,
                    'city' => $address->city,
                    'country' => $address->country,
                    'country_name' => $responseData->billing_address->country_name,
                    'name' => $address->name,
                    'phone' => $address->phone,
                    'vat' => $address->vat,
                    'zip' => $address->zip,
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'email' => $email,
            'comment' => $comment,
            'billing_address_id' => $responseData->billing_address->id,
            'shipping_address_id' => $responseData->shipping_place->id,
        ]);

        Event::assertDispatched(OrderUpdated::class);

        Queue::fake();

        $order = Order::find($this->order->getKey());
        $event = new OrderUpdated($order);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testFullUpdateOrderWithWebHookDispatched($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderUpdated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake([OrderUpdated::class]);

        $email = $this->faker->freeEmail;
        $comment = $this->faker->text(200);
        $address = Address::factory()->create();

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'email' => $email,
            'comment' => $comment,
            'shipping_place' => $address->toArray(),
            'billing_address' => $address->toArray(),
        ]);

        $responseData = $response->getData()->data;
        $response
            ->assertOk()
            ->assertJsonFragment([
                'code' => $this->order->code,
                'status' => [
                    'cancel' => false,
                    'color' => $this->status->color,
                    'description' => $this->status->description,
                    'id' => $this->status->id,
                    'name' => $this->status->name,
                    'hidden' => $this->status->hidden,
                    'no_notifications' => $this->status->no_notifications,
                    'metadata' => [],
                ],
                'shipping_place' => [
                    'id' => $responseData->shipping_place->id,
                    'address' => $address->address,
                    'city' => $address->city,
                    'country' => $address->country ?? null,
                    'country_name' => $responseData->shipping_place->country_name,
                    'name' => $address->name,
                    'phone' => $address->phone,
                    'vat' => $address->vat,
                    'zip' => $address->zip,
                ],
                'billing_address' => [
                    'id' => $responseData->billing_address->id,
                    'address' => $address->address,
                    'city' => $address->city,
                    'country' => $address->country,
                    'country_name' => $responseData->billing_address->country_name,
                    'name' => $address->name,
                    'phone' => $address->phone,
                    'vat' => $address->vat,
                    'zip' => $address->zip,
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'email' => $email,
            'comment' => $comment,
            'shipping_address_id' => $responseData->shipping_place->id,
            'billing_address_id' => $responseData->billing_address->id,
        ]);

        Event::assertDispatched(OrderUpdated::class);

        Bus::fake();

        $order = Order::find($this->order->getKey());
        $event = new OrderUpdated($order);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $order) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $order->getKey()
                && $payload['data_type'] === 'Order'
                && $payload['event'] === 'OrderUpdated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderByEmail($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $email = $this->faker->email();

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'email' => $email,
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'email' => $email,
                'code' => $this->order->code,
                'comment' => $this->order->comment,
                'status' => [
                    'cancel' => false,
                    'color' => $this->status->color,
                    'description' => $this->status->description,
                    'id' => $this->status->getKey(),
                    'name' => $this->status->name,
                    'hidden' => $this->status->hidden,
                    'no_notifications' => $this->status->no_notifications,
                    'metadata' => [],
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'email' => $email,

            // should remain the same
            'comment' => $this->comment,
            'billing_address_id' => $this->addressInvoice->getKey(),
            'shipping_address_id' => $this->addressDelivery->getKey(),
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderByComment($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $comment = $this->faker->text(100);
        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'comment' => $comment,
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'email' => $this->order->email,
                'code' => $this->order->code,
                'comment' => $comment,
                'status' => [
                    'cancel' => false,
                    'color' => $this->status->color,
                    'description' => $this->status->description,
                    'id' => $this->status->getKey(),
                    'name' => $this->status->name,
                    'hidden' => $this->status->hidden,
                    'no_notifications' => $this->status->no_notifications,
                    'metadata' => [],
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'comment' => $comment,

            // should remain the same
            'email' => self::EMAIL,
            'billing_address_id' => $this->addressInvoice->getKey(),
            'shipping_address_id' => $this->addressDelivery->getKey(),
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderWithEmptyComment($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'comment' => '',
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->order->getKey(),
                'comment' => null,
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'comment' => null,
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderWithShippingAddress($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $this->addressDelivery = Address::factory()->create();

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'shipping_place' => $this->addressDelivery->toArray(),
        ]);

        $responseData = $response->getData()->data;

        $response
            ->assertOk()
            ->assertJsonFragment([
                'email' => $this->order->email,
                'code' => $this->order->code,
                'comment' => $this->order->comment,
                'status' => [
                    'cancel' => false,
                    'color' => $this->status->color,
                    'description' => $this->status->description,
                    'id' => $this->status->getKey(),
                    'name' => $this->status->name,
                    'hidden' => $this->status->hidden,
                    'no_notifications' => $this->status->no_notifications,
                    'metadata' => [],
                ],
                'shipping_place' => [
                    'address' => $this->addressDelivery->address,
                    'city' => $this->addressDelivery->city,
                    'country' => $this->addressDelivery->country ?? null,
                    'country_name' => $responseData->shipping_place->country_name,
                    'id' => $responseData->shipping_place->id,
                    'name' => $this->addressDelivery->name,
                    'phone' => $this->addressDelivery->phone,
                    'vat' => $this->addressDelivery->vat,
                    'zip' => $this->addressDelivery->zip,
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'shipping_address_id' => $responseData->shipping_place->id,

            // should remain the same
            'email' => self::EMAIL,
            'comment' => $this->comment,
            'billing_address_id' => $this->addressInvoice->getKey(),
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderWithMissingShippingAddress($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'billing_address' => $this->addressDelivery->toArray(),
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                 // should remain the same
                'shipping_place' => [
                    'address' => $this->addressDelivery->address,
                    'city' => $this->addressDelivery->city,
                    'country' => $this->addressDelivery->country ?? null,
                    'country_name' => $response->getData()->data->shipping_place->country_name,
                    'id' => $response->getData()->data->shipping_place->id,
                    'name' => $this->addressDelivery->name,
                    'phone' => $this->addressDelivery->phone,
                    'vat' => $this->addressDelivery->vat,
                    'zip' => $this->addressDelivery->zip,
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'shipping_address_id' => $this->addressDelivery->getKey(),
            'billing_address_id' => $response->getData()->data->billing_address->id,
        ]);

        $this->checkAddress($this->addressDelivery);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderByInvoiceAddress($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $this->addressInvoice = Address::factory()->create();
        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'billing_address' => $this->addressInvoice->toArray(),
        ]);
        $responseData = $response->getData()->data;

        $response
            ->assertOk()
            ->assertJsonFragment([
                'email' => $this->order->email,
                'code' => $this->order->code,
                'comment' => $this->order->comment,
                'status' => [
                    'cancel' => false,
                    'color' => $this->status->color,
                    'description' => $this->status->description,
                    'id' => $this->status->getKey(),
                    'name' => $this->status->name,
                    'hidden' => $this->status->hidden,
                    'no_notifications' => $this->status->no_notifications,
                    'metadata' => [],
                ],
                'billing_address' => [
                    'address' => $this->addressInvoice->address,
                    'city' => $this->addressInvoice->city,
                    'country' => $this->addressInvoice->country ?? null,
                    'country_name' => $responseData->billing_address->country_name,
                    'id' => $responseData->billing_address->id,
                    'name' => $this->addressInvoice->name,
                    'phone' => $this->addressInvoice->phone,
                    'vat' => $this->addressInvoice->vat,
                    'zip' => $this->addressInvoice->zip,
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'billing_address_id' => $responseData->billing_address->id,

            // should remain the same
            'email' => self::EMAIL,
            'comment' => $this->comment,
            'shipping_address_id' => $this->addressDelivery->getKey(),
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderByMissingInvoiceAddress($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'shipping_address' => $this->addressInvoice->toArray(),
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                 // should remain the same
                'billing_address' => [
                    'address' => $this->addressInvoice->address,
                    'city' => $this->addressInvoice->city,
                    'country' => $this->addressInvoice->country ?? null,
                    'country_name' => $response->getData()->data->billing_address->country_name,
                    'id' => $response->getData()->data->billing_address->id,
                    'name' => $this->addressInvoice->name,
                    'phone' => $this->addressInvoice->phone,
                    'vat' => $this->addressInvoice->vat,
                    'zip' => $this->addressInvoice->zip,
                ],
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'billing_address_id' => $this->addressInvoice->getKey(),
        ]);

        $this->checkAddress($this->addressInvoice);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderByEmptyInvoiceAddress($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'billing_address' => null,
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment(['billing_address' => null]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'billing_address_id' => null,
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderWithShippingMethodTypeAddress($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::ADDRESS,
        ]);

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'shipping_method_id' => $shippingMethod->getKey(),
            'invoice_requested' => true,
            'shipping_place' => $this->address,
        ]);

        $order = Order::find($response->getData()->data->id);

        $response->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'invoice_requested' => true,
            'shipping_place' => $order->shipping_place,
            'shipping_type' => ShippingType::ADDRESS,
            'shipping_address_id' => $this->addressDelivery->getKey(),
        ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $this->addressDelivery->getKey(),
            'name' => $this->address->name,
            'phone' => $this->address->phone,
            'address' => $this->address->address,
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderWithShippingMethodTypePoints($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::POINT,
        ]);

        $pointAddress = Address::factory()->create();

        $shippingMethod->shippingPoints()->attach($pointAddress);

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'shipping_method_id' => $shippingMethod->getKey(),
            'invoice_requested' => true,
            'shipping_place' => $pointAddress->getKey(),
        ]);

        $response->assertOk();

        $order = Order::find($response->getData()->data->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'invoice_requested' => true,
            'shipping_address_id' => $pointAddress->getKey(),
            'shipping_place' => null,
            'shipping_type' => ShippingType::POINT,
        ]);

        $this->assertDatabaseHas('addresses', [
            'id' => $pointAddress->getKey(),
            'name' => $pointAddress->name,
            'phone' => $pointAddress->phone,
            'address' => $pointAddress->address,
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderWithShippingMethodTypePointExternal($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::POINT_EXTERNAL,
        ]);

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'shipping_method_id' => $shippingMethod->getKey(),
            'invoice_requested' => true,
            'shipping_place' => 'Testowy numer domu w testowym mieście',
        ]);

        $response->assertOk();

        $order = Order::find($response->getData()->data->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'invoice_requested' => true,
            'shipping_address_id' => null,
            'shipping_place' => 'Testowy numer domu w testowym mieście',
            'shipping_type' => ShippingType::POINT_EXTERNAL,
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderWithMissingShippingAddressForShippingPlace($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::POINT,
        ]);

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'shipping_method_id' => $shippingMethod->getKey(),
            'invoice_requested' => true,
        ]);

        $response->assertStatus(422);

        Event::assertNotDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderWithMissingShippingPlace($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::POINT_EXTERNAL,
        ]);

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'shipping_method_id' => $shippingMethod->getKey(),
            'invoice_requested' => true,
        ]);

        $response->assertStatus(422);

        Event::assertNotDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShippingListDispatched($user): void
    {
        $this->$user->givePermissionTo(['orders.edit','orders.edit.status']);

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderRequestedShipping',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        $package = PackageTemplate::factory()->create();

        Event::fake([OrderRequestedShipping::class]);

        $this->actingAs($this->$user)
            ->postJson(
                '/orders/id:' . $this->order->getKey() . '/shipping-lists',
                [
                    'package_template_id' => $package->getKey(),
                ]
            )->assertOk()
            ->assertJsonFragment([
                'id' => $this->order->getKey(),
            ]);

        Event::assertDispatched(OrderRequestedShipping::class);

        Bus::fake();

        $event = new OrderRequestedShipping($this->order, $package);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $package) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['order']['id'] === $this->order->getKey()
                && $payload['data']['package']['id'] === $package->getKey()
                && $payload['data_type'] === 'ShippingRequest'
                && $payload['event'] === 'OrderRequestedShipping';
        });
    }
    /**
     * @dataProvider authProvider
     */
    public function testShippingListNotExistingPackageTemplate($user): void
    {
        $this->$user->givePermissionTo(['orders.edit','orders.edit.status']);

        Event::fake([OrderRequestedShipping::class]);

        $package = PackageTemplate::factory()->create();
        $package->delete();

        $this->actingAs($this->$user)
            ->postJson(
                '/orders/id:' . $this->order->getKey() . '/shipping-lists',
                [
                    'package_template_id' => $package->getKey(),
                ]
            )->assertUnprocessable();

        Event::assertNotDispatched(OrderRequestedShipping::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderByShippingNumber($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class, OrderUpdatedShippingNumber::class]);

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/orders/id:{$this->order->getKey()}", [
                'shipping_number' => '1234567890',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'shipping_number' => '1234567890',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'shipping_number' => '1234567890',
        ]);

        // only one event triggered
        Event::assertDispatched(OrderUpdatedShippingNumber::class);
        Event::assertNotDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderByEmptyShippingNumber($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class, OrderUpdatedShippingNumber::class]);

        $this->order->update([
            'shipping_number' => '1234567890',
        ]);

        $this
            ->actingAs($this->$user)
            ->json('PATCH', "/orders/id:{$this->order->getKey()}", [
                'shipping_number' => null,
                'comment' => 'test',
            ])
            ->assertOk()
            ->assertJsonFragment([
                'shipping_number' => null,
                'comment' => 'test',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'shipping_number' => null,
            'comment' => 'test',
        ]);

        // two events
        Event::assertDispatched(OrderUpdatedShippingNumber::class);
        Event::assertDispatched(OrderUpdated::class);
    }

    private function checkAddress(Address $address): void
    {
        $this->assertDatabaseHas('addresses', [
            'id' => $address->getKey(),
            'name' => $address->name,
            'phone' => $address->phone,
            'address' => $address->address,
            'vat' => $address->vat,
            'zip' => $address->zip,
            'city' => $address->city,
            'country' => $address->country,
        ]);
    }
}
