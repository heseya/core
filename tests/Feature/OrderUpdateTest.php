<?php

namespace Tests\Feature;

use App\Events\OrderUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\Address;
use App\Models\Order;
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

    public function setUp(): void
    {
        parent::setUp();

        $shippingMethod = $this->createShippingMethod();
        $this->comment = $this->faker->text(10);
        $this->status = Status::factory()->create();
        $this->addressDelivery = Address::factory()->create();
        $this->addressInvoice = Address::factory()->create();

        $this->order = Order::factory()->create([
            'code' => 'XXXXXX123',
            'email' => self::EMAIL,
            'comment' => $this->comment,
            'status_id' => $this->status->getKey(),
            'shipping_method_id' => $shippingMethod->getKey(),
            'shipping_address_id' => $this->addressDelivery->getKey(),
            'invoice_address_id' => $this->addressInvoice->getKey(),
        ]);
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
            'shipping_address' => $address->toArray(),
            'invoice_address' => $address->toArray(),
        ]);

        $responseData = $response->getData()->data;
        $response
            ->assertOk()
            ->assertJsonFragment([
                'code' => $this->order->code,
                'status' => [
                   "cancel" => false,
                    "color" => $this->status->color,
                    "description" => $this->status->description,
                    "id" => $this->status->id,
                    "name" => $this->status->name,
                    "hidden" => $this->status->hidden,
                    "no_notifications" => $this->status->no_notifications,
                ],
                'shipping_address' => [
                    "id" => $responseData->shipping_address->id,
                    "address" => $address->address,
                    "city" => $address->city,
                    "country" => $address->country ?? null,
                    "country_name" => $responseData->shipping_address->country_name,
                    "name" => $address->name,
                    "phone" => $address->phone,
                    "vat" => $address->vat,
                    "zip" => $address->zip,
                ],
                'invoice_address' => [
                    "id" => $responseData->invoice_address->id,
                    "address" => $address->address,
                    "city" => $address->city,
                    "country" => $address->country,
                    "country_name" => $responseData->invoice_address->country_name,
                    "name" => $address->name,
                    "phone" => $address->phone,
                    "vat" => $address->vat,
                    "zip" => $address->zip,
                ]
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'email' => $email,
            'comment' => $comment,
            'shipping_address_id' => $responseData->shipping_address->id,
            'invoice_address_id' => $responseData->invoice_address->id,
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
    public function testFullUpdateOrderWithWebHookQueue($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderUpdated'
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
            'shipping_address' => $address->toArray(),
            'invoice_address' => $address->toArray(),
        ]);

        $responseData = $response->getData()->data;
        $response
            ->assertOk()
            ->assertJsonFragment([
                'code' => $this->order->code,
                'status' => [
                    "cancel" => false,
                    "color" => $this->status->color,
                    "description" => $this->status->description,
                    "id" => $this->status->id,
                    "name" => $this->status->name,
                    "hidden" => $this->status->hidden,
                    "no_notifications" => $this->status->no_notifications,
                ],
                'shipping_address' => [
                    "id" => $responseData->shipping_address->id,
                    "address" => $address->address,
                    "city" => $address->city,
                    "country" => $address->country ?? null,
                    "country_name" => $responseData->shipping_address->country_name,
                    "name" => $address->name,
                    "phone" => $address->phone,
                    "vat" => $address->vat,
                    "zip" => $address->zip,
                ],
                'invoice_address' => [
                    "id" => $responseData->invoice_address->id,
                    "address" => $address->address,
                    "city" => $address->city,
                    "country" => $address->country,
                    "country_name" => $responseData->invoice_address->country_name,
                    "name" => $address->name,
                    "phone" => $address->phone,
                    "vat" => $address->vat,
                    "zip" => $address->zip,
                ]
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'email' => $email,
            'comment' => $comment,
            'shipping_address_id' => $responseData->shipping_address->id,
            'invoice_address_id' => $responseData->invoice_address->id,
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
                'OrderUpdated'
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
            'shipping_address' => $address->toArray(),
            'invoice_address' => $address->toArray(),
        ]);

        $responseData = $response->getData()->data;
        $response
            ->assertOk()
            ->assertJsonFragment([
                'code' => $this->order->code,
                'status' => [
                    "cancel" => false,
                    "color" => $this->status->color,
                    "description" => $this->status->description,
                    "id" => $this->status->id,
                    "name" => $this->status->name,
                    "hidden" => $this->status->hidden,
                    "no_notifications" => $this->status->no_notifications,
                ],
                'shipping_address' => [
                    "id" => $responseData->shipping_address->id,
                    "address" => $address->address,
                    "city" => $address->city,
                    "country" => $address->country ?? null,
                    "country_name" => $responseData->shipping_address->country_name,
                    "name" => $address->name,
                    "phone" => $address->phone,
                    "vat" => $address->vat,
                    "zip" => $address->zip,
                ],
                'invoice_address' => [
                    "id" => $responseData->invoice_address->id,
                    "address" => $address->address,
                    "city" => $address->city,
                    "country" => $address->country,
                    "country_name" => $responseData->invoice_address->country_name,
                    "name" => $address->name,
                    "phone" => $address->phone,
                    "vat" => $address->vat,
                    "zip" => $address->zip,
                ]
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'email' => $email,
            'comment' => $comment,
            'shipping_address_id' => $responseData->shipping_address->id,
            'invoice_address_id' => $responseData->invoice_address->id,
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
            'email' => $email
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
             'email' => $email,
             'code' => $this->order->code,
             'comment' => $this->order->comment,
             'status' => [
                 "cancel" => false,
                 "color" => $this->status->color,
                 "description" => $this->status->description,
                 "id" => $this->status->getKey(),
                 "name" => $this->status->name,
                 "hidden" => $this->status->hidden,
                 "no_notifications" => $this->status->no_notifications,
             ],
         ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'email' => $email,

            // should remain the same
            'comment' => $this->comment,
            'shipping_address_id' => $this->addressDelivery->getKey(),
            'invoice_address_id' => $this->addressInvoice->getKey(),
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
            'comment' => $comment
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                 'email' => $this->order->email,
                 'code' => $this->order->code,
                 'comment' => $comment,
                 'status' => [
                     "cancel" => false,
                     "color" => $this->status->color,
                     "description" => $this->status->description,
                     "id" => $this->status->getKey(),
                     "name" => $this->status->name,
                     "hidden" => $this->status->hidden,
                     "no_notifications" => $this->status->no_notifications,
                 ],
             ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'comment' => $comment,

            // should remain the same
            'email' => self::EMAIL,
            'shipping_address_id' => $this->addressDelivery->getKey(),
            'invoice_address_id' => $this->addressInvoice->getKey(),
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
            'comment' => ''
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                 'id' => $this->order->getKey(),
                 'comment' => '',
             ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'comment' => '',
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderWithshippingAddress($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $this->addressDelivery = Address::factory()->create();
        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'shipping_address' => $this->addressDelivery->toArray()
        ]);
        $responseData = $response->getData()->data;

        $response
            ->assertOk()
            ->assertJsonFragment([
                 'email' => $this->order->email,
                 'code' => $this->order->code,
                 'comment' => $this->order->comment,
                 'status' => [
                     "cancel" => false,
                     "color" => $this->status->color,
                     "description" => $this->status->description,
                     "id" => $this->status->getKey(),
                     "name" => $this->status->name,
                     "hidden" => $this->status->hidden,
                     "no_notifications" => $this->status->no_notifications,
                 ],
                 'shipping_address' => [
                     "address" => $this->addressDelivery->address,
                     "city" => $this->addressDelivery->city,
                     "country" => $this->addressDelivery->country ?? null,
                     "country_name" => $responseData->shipping_address->country_name,
                     "id" => $responseData->shipping_address->id,
                     "name" => $this->addressDelivery->name,
                     "phone" => $this->addressDelivery->phone,
                     "vat" => $this->addressDelivery->vat,
                     "zip" => $this->addressDelivery->zip,
                 ],
             ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'shipping_address_id' => $responseData->shipping_address->id,

            // should remain the same
            'email' => self::EMAIL,
            'comment' => $this->comment,
            'invoice_address_id' => $this->addressInvoice->getKey(),
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderWithMissingshippingAddress($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'invoice_address' => $this->addressDelivery->toArray()
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                 // should remain the same
                 'shipping_address' => [
                     "address" => $this->addressDelivery->address,
                     "city" => $this->addressDelivery->city,
                     "country" => $this->addressDelivery->country ?? null,
                     "country_name" => $response->getData()->data->shipping_address->country_name,
                     "id" => $response->getData()->data->shipping_address->id,
                     "name" => $this->addressDelivery->name,
                     "phone" => $this->addressDelivery->phone,
                     "vat" => $this->addressDelivery->vat,
                     "zip" => $this->addressDelivery->zip,
                 ],
             ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'shipping_address_id' => $this->addressDelivery->getKey(),
            'invoice_address_id' =>  $response->getData()->data->invoice_address->id,
        ]);

        $this->checkAddress($this->addressDelivery);

        Event::assertDispatched(OrderUpdated::class);
    }

    private function checkAddress(Address $address): void
    {
        $this->assertDatabaseHas('addresses', [
            'id' => $address->getKey(),
            'name' => $address->name,
            'phone' =>  $address->phone,
            'address' =>  $address->address,
            'vat' =>  $address->vat,
            'zip' =>  $address->zip,
            'city' =>  $address->city,
            'country' =>  $address->country,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOrderWithEmptyshippingAddress($user): void
    {
        $this->$user->givePermissionTo('orders.edit');

        Event::fake([OrderUpdated::class]);

        $response = $this->actingAs($this->$user)->patchJson('/orders/id:' . $this->order->getKey(), [
            'shipping_address' => null
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment(['shipping_address' => null]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'invoice_address_id' => $this->addressInvoice->getKey(),
            'shipping_address_id' => null,
        ]);

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
            'invoice_address' => $this->addressInvoice->toArray()
        ]);
        $responseData = $response->getData()->data;

        $response
            ->assertOk()
            ->assertJsonFragment([
                 'email' => $this->order->email,
                 'code' => $this->order->code,
                 'comment' => $this->order->comment,
                 'status' => [
                     "cancel" => false,
                     "color" => $this->status->color,
                     "description" => $this->status->description,
                     "id" => $this->status->getKey(),
                     "name" => $this->status->name,
                     "hidden" => $this->status->hidden,
                     "no_notifications" => $this->status->no_notifications,
                 ],
                 'invoice_address' => [
                     "address" => $this->addressInvoice->address,
                     "city" => $this->addressInvoice->city,
                     "country" => $this->addressInvoice->country ?? null,
                     "country_name" => $responseData->invoice_address->country_name,
                     "id" => $responseData->invoice_address->id,
                     "name" => $this->addressInvoice->name,
                     "phone" => $this->addressInvoice->phone,
                     "vat" => $this->addressInvoice->vat,
                     "zip" => $this->addressInvoice->zip,
                 ],
             ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'invoice_address_id' => $responseData->invoice_address->id,

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
            'shipping_address' => $this->addressInvoice->toArray()
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                 // should remain the same
                 'invoice_address' => [
                     "address" => $this->addressInvoice->address,
                     "city" => $this->addressInvoice->city,
                     "country" => $this->addressInvoice->country ?? null,
                     "country_name" => $response->getData()->data->invoice_address->country_name,
                     "id" => $response->getData()->data->invoice_address->id,
                     "name" => $this->addressInvoice->name,
                     "phone" => $this->addressInvoice->phone,
                     "vat" => $this->addressInvoice->vat,
                     "zip" => $this->addressInvoice->zip,
                 ],
             ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'invoice_address_id' => $this->addressInvoice->getKey(),
            'shipping_address_id' => $response->getData()->data->shipping_address->id,
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
            'invoice_address' => null
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment(['invoice_address' => null]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->getKey(),
            'shipping_address_id' => $this->addressDelivery->getKey(),
            'invoice_address_id' => null,
        ]);

        Event::assertDispatched(OrderUpdated::class);
    }
}
