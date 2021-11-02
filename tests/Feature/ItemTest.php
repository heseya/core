<?php

namespace Tests\Feature;

use App\Events\ItemCreated;
use App\Events\ItemDeleted;
use App\Events\ItemUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\WebHook;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class ItemTest extends TestCase
{
    private Item $item;

    private array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->item = Item::factory()->create();

        Deposit::factory()->create([
            'item_id' => $this->item->id,
        ]);

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->item->getKey(),
            'name' => $this->item->name,
            'sku' => $this->item->sku,
            'quantity' => $this->item->quantity,
        ];
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/items');
        $response->assertForbidden();
    }

    public function testIndex(): void
    {
        $this->user->givePermissionTo('items.show');

        $response = $this->actingAs($this->user)->getJson('/items');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    public function testViewUnauthorized(): void
    {
        $response = $this->getJson('/items/id:' . $this->item->getKey());
        $response->assertForbidden();
    }

    public function testView(): void
    {
        $this->user->givePermissionTo('items.show_details');

        $response = $this->actingAs($this->user)
            ->getJson('/items/id:' . $this->item->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected]);
    }

    public function testCreateUnauthorized(): void
    {
        Event::fake(ItemCreated::class);

        $response = $this->postJson('/items');
        $response->assertForbidden();

        Event::assertNotDispatched(ItemCreated::class);
    }

    public function testCreate(): void
    {
        $this->user->givePermissionTo('items.add');

        Event::fake(ItemCreated::class);

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
        ];

        $response = $this->actingAs($this->user)->postJson('/items', $item);
        $response
            ->assertCreated()
            ->assertJson(['data' => $item]);

        $this->assertDatabaseHas('items', $item);

        Event::assertDispatched(ItemCreated::class);
    }

    public function testCreateWithWebHook(): void
    {
        $this->user->givePermissionTo('items.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemCreated'
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
        ];

        $response = $this->actingAs($this->user)->postJson('/items', $item);
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

    public function testUpdateUnauthorized(): void
    {
        Event::fake(ItemUpdated::class);

        $response = $this->patchJson('/items/id:' . $this->item->getKey());
        $response->assertForbidden();

        Event::assertNotDispatched(ItemUpdated::class);
    }

    public function testUpdate(): void
    {
        $this->user->givePermissionTo('items.edit');

        Event::fake(ItemUpdated::class);

        $item = [
            'name' => 'Test 2',
            'sku' => 'TES/T2',
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $item]);

        $this->assertDatabaseHas('items', $item + ['id' => $this->item->getKey()]);

        Event::assertDispatched(ItemUpdated::class);
    }

    public function testUpdateWithWebHook(): void
    {
        $this->user->givePermissionTo('items.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemUpdated'
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $item = [
            'name' => 'Test 2',
            'sku' => 'TES/T2',
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $item]);

        $this->assertDatabaseHas('items', $item + ['id' => $this->item->getKey()]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ItemUpdated;
        });

        $item = Item::find($response->getData()->data->id);

        $event = new ItemUpdated($item);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $item) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $item->getKey()
                && $payload['data_type'] === 'Item'
                && $payload['event'] === 'ItemUpdated';
        });
    }

    public function testDeleteUnauthorized(): void
    {
        Event::fake(ItemDeleted::class);

        $response = $this->deleteJson('/items/id:' . $this->item->getKey());
        $response->assertForbidden();
        $this->assertDatabaseHas('items', $this->item->toArray());

        Event::assertNotDispatched(ItemDeleted::class);
    }

    public function testDelete(): void
    {
        $this->user->givePermissionTo('items.remove');

        Event::fake(ItemDeleted::class);

        $response = $this->actingAs($this->user)
            ->deleteJson('/items/id:' . $this->item->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($this->item);

        Event::assertDispatched(ItemDeleted::class);
    }

    public function testDeleteWithWebHook(): void
    {
        $this->user->givePermissionTo('items.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemDeleted'
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->user)
            ->deleteJson('/items/id:' . $this->item->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($this->item);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof ItemDeleted;
        });

        $item = $this->item;

        $event = new ItemDeleted($item);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $item) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $item->getKey()
                && $payload['data_type'] === 'Item'
                && $payload['event'] === 'ItemDeleted';
        });
    }
}
