<?php

namespace Tests\Feature\Items;

use App\Enums\ErrorCode;
use App\Events\ItemCreated;
use App\Listeners\WebHookEventListener;
use App\Models\Item;
use App\Models\WebHook;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Ramsey\Uuid\Uuid;
use Spatie\WebhookServer\CallWebhookJob;

class ItemCreateTest extends ItemTestCase
{
    public function testCreateUnauthorized(): void
    {
        Event::fake(ItemCreated::class);

        $response = $this->postJson('/items');
        $response->assertForbidden();

        Event::assertNotDispatched(ItemCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate(string $user): void
    {
        $this->{$user}->givePermissionTo('items.add');

        Event::fake(ItemCreated::class);

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
        ];

        $response = $this->actingAs($this->{$user})->postJson('/items', $item);
        $response
            ->assertCreated()
            ->assertJson(['data' => $item]);

        $this->assertDatabaseHas('items', $item);

        Event::assertDispatched(ItemCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithUuid(string $user): void
    {
        $this->{$user}->givePermissionTo('items.add');

        Event::fake(ItemCreated::class);

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
            'id' => Uuid::uuid4()->toString(),
        ];

        $response = $this->actingAs($this->{$user})->postJson('/items', $item);
        $response
            ->assertCreated()
            ->assertJson(['data' => $item]);

        $this->assertDatabaseHas('items', $item);

        Event::assertDispatched(ItemCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithoutPermission(string $user): void
    {
        Event::fake(ItemCreated::class);

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
        ];

        $response = $this->actingAs($this->{$user})->postJson('/items', $item);

        $response
            ->assertJsonFragment([
                'code' => 403,
                'key' => ErrorCode::FORBIDDEN->name,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadata(string $user): void
    {
        $this->{$user}->givePermissionTo('items.add');

        Event::fake(ItemCreated::class);

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
        ];

        $metadata = [
            'metadata' => [
                'attributeMeta' => 'attributeValue',
            ],
        ];

        $response = $this->actingAs($this->{$user})->postJson('/items', $item + $metadata);
        $response
            ->assertCreated()
            ->assertJson(['data' => $item + $metadata]);

        $this->assertDatabaseHas('items', $item);

        Event::assertDispatched(ItemCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadataPrivate(string $user): void
    {
        $this->{$user}->givePermissionTo(['items.add', 'items.show_metadata_private']);

        Event::fake(ItemCreated::class);

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
        ];

        $metadata = [
            'metadata_private' => [
                'attributeMetaPriv' => 'attributeValuePriv',
            ],
        ];

        $response = $this->actingAs($this->{$user})->postJson('/items', $item + $metadata);
        $response
            ->assertCreated()
            ->assertJson(['data' => $item + $metadata]);

        $this->assertDatabaseHas('items', $item);

        Event::assertDispatched(ItemCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWebHook(string $user): void
    {
        $this->{$user}->givePermissionTo('items.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemCreated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
        ];

        $response = $this->actingAs($this->{$user})->postJson('/items', $item);
        $response
            ->assertCreated()
            ->assertJson(['data' => $item]);

        $this->assertDatabaseHas('items', $item);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ItemCreated;
        });

        $item = Item::find($response->getData()->data->id);

        $event = new ItemCreated($item);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $item) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $item->getKey()
                && $payload['data_type'] === 'Item'
                && $payload['event'] === 'ItemCreated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateValidationInvalidBothShippingTimeAndDate(string $user): void
    {
        $this->{$user}->givePermissionTo('items.add');

        Event::fake(ItemCreated::class);

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
            'unlimited_stock_shipping_time' => 10,
            'unlimited_stock_shipping_date' => '1999-02-01',
        ];

        $this->actingAs($this->{$user})->postJson('/items', $item)->assertStatus(422);

        Event::assertNotDispatched(ItemCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateUnlimitedShippingTime(string $user): void
    {
        $this->{$user}->givePermissionTo('items.add');

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
            'unlimited_stock_shipping_time' => 5,
            'unlimited_stock_shipping_date' => null,
        ];

        $response = $this->actingAs($this->{$user})->postJson('/items', $item);
        $response
            ->assertCreated()
            ->assertJson(['data' => $item]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateUnlimitedShippingDate(string $user): void
    {
        $this->{$user}->givePermissionTo('items.add');

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
            'unlimited_stock_shipping_time' => null,
            'unlimited_stock_shipping_date' => Carbon::now()->startOfDay()->addDays(5)->toIso8601String(),
        ];

        $response = $this->actingAs($this->{$user})->postJson('/items', $item);
        $response
            ->assertCreated()
            ->assertJson(['data' => $item]);
    }
}
