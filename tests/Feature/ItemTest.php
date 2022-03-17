<?php

namespace Tests\Feature;

use App\Events\ItemCreated;
use App\Events\ItemDeleted;
use App\Events\ItemUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\WebHook;
use Carbon\Carbon;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
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
            'item_id' => $this->item->getKey(),
        ]);

        $this->item->refresh();

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->item->getKey(),
            'name' => $this->item->name,
            'sku' => $this->item->sku,
            'quantity' => $this->item->quantity,
            'metadata' => [],
        ];
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/items');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('items.show');

        $this
            ->actingAs($this->$user)
            ->getJson('/items')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);

        $this->assertQueryCountLessThan(10);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexPerformance($user): void
    {
        $this->$user->givePermissionTo('items.show');

        Item::factory()->count(499)->create();

        $this
            ->actingAs($this->$user)
            ->getJson('/items?limit=500')
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(10);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilterByAvailable($user): void
    {
        $this->$user->givePermissionTo('items.show');

        Deposit::factory([
            'quantity' => 10,
        ])->create([
            'item_id' => $this->item->getKey(),
        ]);

        $this->item->refresh();

        $item_sold_out = Item::factory()->create();

        $this
            ->actingAs($this->$user)
            ->json('GET', '/items', ['sold_out' => 0])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->item->getKey(),
                    'name' => $this->item->name,
                    'sku' => $this->item->sku,
                    'quantity' => $this->item->quantity,
                ],
            ]]);

        $this->assertQueryCountLessThan(10);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilterBySoldOut($user): void
    {
        $this->$user->givePermissionTo('items.show');

        Deposit::factory([
            'quantity' => 10,
        ])->create([
            'item_id' => $this->item->getKey(),
        ]);

        $item_sold_out = Item::factory()->create();

        $this
            ->actingAs($this->$user)
            ->json('GET', '/items', ['sold_out' => 1])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => [
                    'id' => $item_sold_out->getKey(),
                    'name' => $item_sold_out->name,
                    'sku' => $item_sold_out->sku,
                    'quantity' => $item_sold_out->quantity,
                ],
            ]]);

        $this->assertQueryCountLessThan(10);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilterBySoldOutAndDay($user): void
    {
        $this->$user->givePermissionTo('items.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/items', [
                'sold_out' => 1,
                'day' => Carbon::now(),
            ])
            ->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSortByQuantityAndFilterByDay($user): void
    {
        $this->$user->givePermissionTo('items.show');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/items', [
                'sort' => 'quantity:asc',
                'day' => Carbon::now(),
            ])
            ->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilterByDay($user): void
    {
        $this->$user->givePermissionTo('items.show');

        $created_at = Carbon::yesterday()->addHours(12);

        $item2 = Item::factory()->create([
            'created_at' => $created_at,
        ]);
        Deposit::factory([
            'quantity' => 5,
            'created_at' => $created_at,
        ])->create([
            'item_id' => $item2->getKey(),
        ]);

        Deposit::factory([
            'quantity' => 5,
        ])->create([
            'item_id' => $item2->getKey(),
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/items', ['day' => $created_at->format('Y-m-d')])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $item2->getKey(),
                'name' => $item2->name,
                'sku' => $item2->sku,
                'quantity' => 5,
            ]);

        $this->assertQueryCountLessThan(10);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilterByDayWithHour($user): void
    {
        $this->$user->givePermissionTo('items.show');

        $item2 = Item::factory()->create([
            'created_at' => Carbon::yesterday(),
        ]);
        Deposit::factory([
            'quantity' => 5,
            'created_at' => Carbon::yesterday(),
        ])->create([
            'item_id' => $item2->getKey(),
        ]);

        Deposit::factory([
            'quantity' => 5,
        ])->create([
            'item_id' => $item2->getKey(),
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/items', ['day' => Carbon::yesterday()])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $item2->getKey(),
                'name' => $item2->name,
                'sku' => $item2->sku,
                'quantity' => 5,
            ]);

        $this->assertQueryCountLessThan(10);
    }

    public function testViewUnauthorized(): void
    {
        $response = $this->getJson('/items/id:' . $this->item->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testView($user): void
    {
        $this->$user->givePermissionTo('items.show_details');

        $this
            ->actingAs($this->$user)
            ->getJson('/items/id:' . $this->item->getKey())
            ->assertOk()
            ->assertJson(['data' => $this->expected]);

        $this->assertQueryCountLessThan(10);
    }

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
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('items.add');

        Event::fake(ItemCreated::class);

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
        ];

        $response = $this->actingAs($this->$user)->postJson('/items', $item);
        $response
            ->assertCreated()
            ->assertJson(['data' => $item]);

        $this->assertDatabaseHas('items', $item);

        Event::assertDispatched(ItemCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWebHook($user): void
    {
        $this->$user->givePermissionTo('items.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemCreated'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
        ];

        $response = $this->actingAs($this->$user)->postJson('/items', $item);
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

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->$user->givePermissionTo('items.edit');

        Event::fake(ItemUpdated::class);

        $item = [
            'name' => 'Test 2',
            'sku' => 'TES/T2',
        ];

        $response = $this->actingAs($this->$user)->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $item]);

        $this->assertDatabaseHas('items', $item + ['id' => $this->item->getKey()]);

        Event::assertDispatched(ItemUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithWebHook($user): void
    {
        $this->$user->givePermissionTo('items.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemUpdated'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $item = [
            'name' => 'Test 2',
            'sku' => 'TES/T2',
        ];

        $response = $this->actingAs($this->$user)->patchJson(
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

        $response = $this->deleteJson('/items/id:' . $this->item->getKey())
            ->assertForbidden();

        $this->assertDatabaseHas('items', [
            'id' => $this->item->getKey(),
            'sku' => $this->item->sku,
            'name' => $this->item->name,
        ]);

        Event::assertNotDispatched(ItemDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('items.remove');

        Event::fake(ItemDeleted::class);

        $this
            ->actingAs($this->$user)
            ->deleteJson('/items/id:' . $this->item->getKey())
            ->assertNoContent();

        $this->assertSoftDeleted($this->item);

        Event::assertDispatched(ItemDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithWebHook($user): void
    {
        $this->$user->givePermissionTo('items.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemDeleted'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->$user)
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
