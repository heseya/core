<?php

namespace Tests\Feature;

use App\Enums\ErrorCode;
use App\Enums\ValidationError;
use App\Events\ItemCreated;
use App\Events\ItemDeleted;
use App\Events\ItemUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\Product;
use App\Models\WebHook;
use App\Services\SchemaCrudService;
use Domain\Currency\Currency;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Ramsey\Uuid\Uuid;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class ItemTest extends TestCase
{
    private Item $item;

    private array $expected;

    private SchemaCrudService $schemaCrudService;
    private Currency $currency = Currency::DEFAULT;

    public function setUp(): void
    {
        parent::setUp();

        $this->item = Item::factory()->create();

        Deposit::factory()->create([
            'item_id' => $this->item->getKey(),
        ]);

        $this->item->refresh();

        // Expected response
        $this->expected = [
            'id' => $this->item->getKey(),
            'name' => $this->item->name,
            'sku' => $this->item->sku,
            'quantity' => $this->item->quantity,
            'metadata' => [],
        ];

        $this->schemaCrudService = App::make(SchemaCrudService::class);
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/items');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        $this
            ->actingAs($this->{$user})
            ->getJson('/items')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);

        $this->assertQueryCountLessThan(12);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByIds(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        Item::factory()->count(10)->create();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/items', [
                'ids' => [
                    $this->item->getKey(),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexPerformance(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        Item::factory()->count(499)->create();

        $this
            ->actingAs($this->{$user})
            ->getJson('/items?limit=500')
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(12);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilterByAvailable(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        Deposit::factory([
            'quantity' => 10,
        ])->create([
            'item_id' => $this->item->getKey(),
        ]);

        $this->item->refresh();

        $item_sold_out = Item::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/items', ['sold_out' => 0])
            ->assertOk()
            ->assertJsonMissing(['id' => $item_sold_out->getKey()])
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $this->item->getKey(),
                        'name' => $this->item->name,
                        'sku' => $this->item->sku,
                        'quantity' => $this->item->quantity,
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(12);
    }

    /**
     * @dataProvider authWithTwoBooleansProvider
     */
    public function testIndexFilterBySoldOut($user, $boolean, $booleanValue): void
    {
        $this->{$user}->givePermissionTo('items.show');

        Deposit::factory([
            'quantity' => 10,
        ])->create([
            'item_id' => $this->item->getKey(),
        ]);

        $item_sold_out = Item::factory()->create();

        $itemId = $booleanValue ? $item_sold_out->getKey() : $this->item->getKey();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/items', ['sold_out' => $boolean])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $itemId,
            ]);

        $this->assertQueryCountLessThan(12);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilterBySoldOutAndDay(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/items', [
                'sold_out' => 1,
                'day' => Carbon::now(),
            ])
            ->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSortByQuantityAndFilterByDay(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/items', [
                'sort' => 'quantity:asc',
                'day' => Carbon::now(),
            ])
            ->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilterByDay(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        $created_at = Carbon::yesterday()->startOfDay()->addHours(12);

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
            ->actingAs($this->{$user})
            ->json('GET', '/items', ['day' => $created_at->format('Y-m-d')])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $item2->getKey(),
                'name' => $item2->name,
                'sku' => $item2->sku,
                'quantity' => 5,
            ]);

        $this->assertQueryCountLessThan(12);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilterByDayWithHour(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

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
            ->actingAs($this->{$user})
            ->json('GET', '/items', ['day' => Carbon::yesterday()])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $item2->getKey(),
                'name' => $item2->name,
                'sku' => $item2->sku,
                'quantity' => 5,
            ]);

        $this->assertQueryCountLessThan(12);
    }

    public function testViewUnauthorized(): void
    {
        $response = $this->getJson('/items/id:' . $this->item->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testView(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show_details');

        $this
            ->actingAs($this->{$user})
            ->getJson('/items/id:' . $this->item->getKey())
            ->assertOk()
            ->assertJson(['data' => $this->expected + ['products' => [], 'schemas' => []]]);

        $this->assertQueryCountLessThan(11);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewWithProducts(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show_details');

        $product1 = Product::factory()->create(['public' => true]);
        $product1->items()->attach([$this->item->getKey() => [
            'required_quantity' => 1,
        ]]);

        $product2 = Product::factory()->create(['public' => true]);
        $product2->items()->attach([$this->item->getKey() => [
            'required_quantity' => 1,
        ]]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/items/id:' . $this->item->getKey())
            ->assertOk()
            ->assertJson(['data' => $this->expected])
            ->assertJsonCount(2, 'data.products')
            ->assertJsonFragment([
                'id' => $product1->getKey(),
                'name' => $product1->name,
            ])
            ->assertJsonFragment([
                'id' => $product2->getKey(),
                'name' => $product2->name,
            ]);

        $this->assertQueryCountLessThan(18);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewWithSchemas(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show_details');

        $schema1 = $this->schemaCrudService->store(FakeDto::schemaDto([
            'type' => 'select',
            'prices' => [['value' => 0, 'currency' => $this->currency->value]],
            'hidden' => false,
            'required' => true,
        ]));

        $option1 = $schema1->options()->create([
            'name' => 'XL',
            'prices' => [['value' => 0, 'currency' => $this->currency->value]],
        ]);
        $option1->items()->sync([$this->item->getKey()]);

        $schema2 = $this->schemaCrudService->store(FakeDto::schemaDto([
            'type' => 'select',
            'prices' => [['value' => 0, 'currency' => $this->currency->value]],
            'hidden' => false,
            'required' => false,
        ]));

        $option2 = $schema2->options()->create([
            'name' => 'XL',
            'prices' => [['value' => 0, 'currency' => $this->currency->value]],
        ]);
        $option2->items()->sync([$this->item->getKey()]);

        $option3 = $schema2->options()->create([
            'name' => 'XL',
            'prices' => [['value' => 0, 'currency' => $this->currency->value]],
        ]);
        $option3->items()->sync([$this->item->getKey()]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/items/id:' . $this->item->getKey())
            ->assertOk()
            ->assertJson(['data' => $this->expected])
            ->assertJsonCount(2, 'data.schemas')
            ->assertJsonFragment([
                'id' => $schema1->getKey(),
                'name' => $schema1->name,
            ])
            ->assertJsonFragment([
                'id' => $schema2->getKey(),
                'name' => $schema2->name,
            ]);

        $this->assertQueryCountLessThan(12);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewWrongId(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show_details');

        $this
            ->actingAs($this->{$user})
            ->getJson('/items/id:its-not-id')
            ->assertNotFound();

        $this
            ->actingAs($this->{$user})
            ->getJson('/items/id:' . $this->item->getKey() . $this->item->getKey())
            ->assertNotFound();
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
    public function testUpdate(string $user): void
    {
        $this->{$user}->givePermissionTo('items.edit');

        Event::fake(ItemUpdated::class);

        $item = [
            'name' => 'Test 2',
            'sku' => 'TES/T2',
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
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
    public function testUpdateWithPartialData(string $user): void
    {
        $this->{$user}->givePermissionTo('items.edit');

        Event::fake(ItemUpdated::class);

        $item = [
            'name' => 'Test 2',
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'name' => 'Test 2',
                    'sku' => $this->item->sku,
                ],
            ]);

        $this->assertDatabaseHas('items', [
            'id' => $this->item->getKey(),
            'sku' => $this->item->sku,
            'name' => 'Test 2',
        ]);

        Event::assertDispatched(ItemUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithPartialDataSku(string $user): void
    {
        $this->{$user}->givePermissionTo('items.edit');

        Event::fake(ItemUpdated::class);

        $item = [
            'sku' => 'TES/T3',
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        );

        $response
            ->assertOk()
            ->assertJson([
                'data' => [
                    'name' => $this->item->name,
                    'sku' => $item['sku'],
                ],
            ]);

        $this->assertDatabaseHas('items', [
            'id' => $this->item->getKey(),
            'sku' => $item['sku'],
            'name' => $this->item->name,
        ]);

        Event::assertDispatched(ItemUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithInvalidUnlimitedDate($user): void
    {
        $this->{$user}->givePermissionTo('items.edit');

        $this->item->update([
            'shipping_date' => now(),
        ]);

        Deposit::factory()->create([
            'quantity' => 20,
            'from_unlimited' => false,
            'shipping_date' => now(),
            'item_id' => $this->item->getKey(),
        ]);

        $item = [
            'unlimited_stock_shipping_date' => now()->subDay(),
        ];

        $this->actingAs($this->{$user})->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        )
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::UNLIMITEDSHIPPINGDATE,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithInvalidUnlimitedTime($user): void
    {
        $this->{$user}->givePermissionTo('items.edit');

        $this->item->update([
            'shipping_time' => 4,
        ]);

        Deposit::factory()->create([
            'quantity' => 20,
            'from_unlimited' => false,
            'shipping_time' => 4,
            'item_id' => $this->item->getKey(),
        ]);

        $item = [
            'unlimited_stock_shipping_time' => 2,
        ];

        $this->actingAs($this->{$user})->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        )
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::UNLIMITEDSHIPPINGTIME,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithWebHook(string $user): void
    {
        $this->{$user}->givePermissionTo('items.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemUpdated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $item = [
            'name' => 'Test 2',
            'sku' => 'TES/T2',
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
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

        $this
            ->json('DELETE', '/items/id:' . $this->item->getKey())
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
    public function testDelete(string $user): void
    {
        $this->{$user}->givePermissionTo('items.remove');

        Event::fake(ItemDeleted::class);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/items/id:' . $this->item->getKey())
            ->assertNoContent();

        $this->assertSoftDeleted($this->item);

        Event::assertDispatched(ItemDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithWebHook(string $user): void
    {
        $this->{$user}->givePermissionTo('items.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemDeleted',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->{$user})
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
    public function testUpdateValidationInvalidBothUnlimitedShippingTimeAndDate(string $user): void
    {
        $this->{$user}->givePermissionTo('items.edit');

        Event::fake(ItemUpdated::class);

        $item = [
            'sku' => 'TES/T3',
            'unlimited_stock_shipping_time' => 10,
            'unlimited_stock_shipping_date' => '1999-02-01',
        ];

        $this->actingAs($this->{$user})->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        )->assertStatus(422);

        Event::assertNotDispatched(ItemUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateValidationUnlimitedShippingDateLesserThenShippingDate(string $user): void
    {
        $this->{$user}->givePermissionTo('items.edit');

        Event::fake(ItemUpdated::class);

        Deposit::factory()->create([
            'item_id' => $this->item->getKey(),
            'quantity' => 2.0,
            'shipping_date' => Carbon::now()->startOfDay()->addDays(4)->toDateTimeString(),
        ]);

        $item = [
            'sku' => 'TES/T3',
            'unlimited_stock_shipping_date' => Carbon::now()->startOfDay()->addDay()->toDateTimeString(),
        ];

        $this->actingAs($this->{$user})->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        )->assertStatus(422);

        Event::assertNotDispatched(ItemUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateValidationUnlimitedShippingTimeLesserThenShippingTime(string $user): void
    {
        $this->{$user}->givePermissionTo('items.edit');

        Event::fake(ItemUpdated::class);
        $time = 4;
        Deposit::factory()->create([
            'item_id' => $this->item->getKey(),
            'quantity' => 2.0,
            'shipping_time' => $time,
        ]);

        $item = [
            'sku' => 'TES/T3',
            'unlimited_stock_shipping_time' => $time - 3,
        ];

        $this->actingAs($this->{$user})->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        )->assertStatus(422);

        Event::assertNotDispatched(ItemUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateUnlimitedShippingTime(string $user): void
    {
        $this->{$user}->givePermissionTo('items.edit');

        $this->item->deposits->first->update([
            'shipping_time' => null,
        ]);

        $time = 4;
        Deposit::factory()->create([
            'item_id' => $this->item->getKey(),
            'quantity' => 2.0,
            'shipping_time' => $time,
        ]);
        Deposit::factory()->create([
            'item_id' => $this->item->getKey(),
            'quantity' => -2.0,
            'shipping_time' => $time,
        ]);
        Deposit::factory()->create([
            'item_id' => $this->item->getKey(),
            'quantity' => 2.0,
            'shipping_time' => $time - 2,
        ]);

        $item = [
            'sku' => 'TES/T3',
            'unlimited_stock_shipping_time' => $time - 1,
        ];

        $this->actingAs($this->{$user})->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        )->assertOk()
            ->assertJson(['data' => $item]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateUnlimitedShippingTimeNull(string $user): void
    {
        $this->{$user}->givePermissionTo('items.edit');

        $item = [
            'sku' => 'TES/T3',
            'unlimited_stock_shipping_time' => null,
        ];

        $this->actingAs($this->{$user})->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        )->assertOk()
            ->assertJson(['data' => $item]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateUnlimitedShippingDate(string $user): void
    {
        $this->{$user}->givePermissionTo('items.edit');
        $date = Carbon::today()->addDays(4);

        Deposit::factory()->create([
            'item_id' => $this->item->getKey(),
            'quantity' => 2.0,
            'shipping_date' => $date->addDays(4)->toDateTimeString(),
        ]);
        Deposit::factory()->create([
            'item_id' => $this->item->getKey(),
            'quantity' => -2.0,
            'shipping_date' => $date->addDays(4)->toDateTimeString(),
        ]);
        Deposit::factory()->create([
            'item_id' => $this->item->getKey(),
            'quantity' => 2.0,
            'shipping_date' => $date->addDays(2)->toDateTimeString(),
        ]);

        $item = [
            'sku' => 'TES/T3',
            'unlimited_stock_shipping_date' => $date->addDays(3)->toIso8601String(),
        ];

        $this->actingAs($this->{$user})->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        )->assertOk()
            ->assertJson(['data' => $item]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateUnlimitedShippingDateWithSameDateAsDeposit(string $user): void
    {
        $this->{$user}->givePermissionTo('items.edit');
        $date = Carbon::today()->addDays(4);

        Deposit::factory()->create([
            'item_id' => $this->item->getKey(),
            'quantity' => 2.0,
            'shipping_date' => $date->addDays(4)->toDateTimeString(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->patchJson('/items/id:' . $this->item->getKey(), [
                'unlimited_stock_shipping_date' => $date->toDateTimeString(),
            ])
            ->assertOk()
            ->assertJson(['data' => [
                'unlimited_stock_shipping_date' => $date->toIso8601String(),
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateUnlimitedShippingDateNull(string $user): void
    {
        $this->{$user}->givePermissionTo('items.edit');

        $item = [
            'sku' => 'TES/T3',
            'unlimited_stock_shipping_date' => null,
        ];

        $this->actingAs($this->{$user})->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        )->assertOk()
            ->assertJson(['data' => $item]);
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

    /**
     * @dataProvider authProvider
     */
    public function testShowWhitAvailability(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show_details');

        $item = Item::factory()->create();

        $time = 4;
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_time' => $time,
        ]);
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_time' => $time,
        ]);
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_time' => $time + 5,
        ]);
        $date = Carbon::now()->startOfDay()->addDays(5)->toIso8601String();
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_date' => $date,
            'shipping_time' => null,
        ]);
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_time' => null,
        ]);
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_time' => $time + 1,
        ]);
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => -2.0,
            'shipping_time' => $time + 1,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/items/id:' . $item->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'availability' => [
                    ['quantity' => 2, 'shipping_time' => null, 'shipping_date' => null, 'from_unlimited' => false],
                    ['quantity' => 2, 'shipping_time' => null, 'shipping_date' => $date, 'from_unlimited' => false],
                    ['quantity' => 4, 'shipping_time' => 4, 'shipping_date' => null, 'from_unlimited' => false],
                    ['quantity' => 2, 'shipping_time' => 9, 'shipping_date' => null, 'from_unlimited' => false],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWhitAvailability(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');
        $this->{$user}->givePermissionTo('items.show_details');
        $this->{$user}->givePermissionTo('deposits.add');

        $item = Item::factory()->create();

        $deposit = [
            'quantity' => 10,
            'shipping_time' => 10,
        ];

        $this->actingAs($this->{$user})->postJson(
            "/items/id:{$item->getKey()}/deposits",
            $deposit,
        )->assertCreated();

        $this
            ->actingAs($this->{$user})
            ->getJson('/items/id:' . $item->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'availability' => [
                    ['quantity' => 10, 'shipping_time' => 10, 'shipping_date' => null, 'from_unlimited' => false],
                ],
            ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/items')
            ->assertOk()
            ->assertJsonFragment([
                'availability' => [
                    ['quantity' => 10, 'shipping_time' => 10, 'shipping_date' => null, 'from_unlimited' => false],
                ],
            ]);
    }
}
