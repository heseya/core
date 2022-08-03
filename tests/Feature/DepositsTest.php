<?php

namespace Tests\Feature;

use App\Events\ItemUpdatedQuantity;
use App\Listeners\WebHookEventListener;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\WebHook;
use App\Services\Contracts\DepositServiceContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class DepositsTest extends TestCase
{
    private Item $item;
    private array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->item = Item::factory()->create();

        $deposit = Deposit::factory()->create([
            'item_id' => $this->item->getKey(),
        ]);

        $this->item->refresh();

        $this->expected = [
            'id' => $deposit->getKey(),
            'quantity' => $deposit->quantity,
            'item_id' => $deposit->item_id,
        ];
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/deposits');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('deposits.show');

        $response = $this->actingAs($this->$user)->getJson('/deposits');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    0 => $this->expected,
                ],
            ]);
    }

    public function testViewUnauthorized(): void
    {
        $response = $this->getJson('/items/id:' . $this->item->getKey() . '/deposits');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewWrongId($user): void
    {
        $this->$user->givePermissionTo('deposits.show');

        $this
            ->getJson('/items/id:its-not-id/deposits')
            ->assertNotFound();

        $this
            ->getJson('/items/id:' . $this->item->getKey() . $this->item->getKey() . '/deposits')
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testView($user): void
    {
        $this->$user->givePermissionTo('deposits.show');

        $response = $this->actingAs($this->$user)
            ->getJson('/items/id:' . $this->item->getKey() . '/deposits');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    0 => $this->expected,
                ],
            ]);
    }

    public function testCreateUnauthorized(): void
    {
        Event::fake(ItemUpdatedQuantity::class);

        $deposit = [
            'quantity' => 12.5,
        ];

        $response = $this->postJson(
            '/items/id:' . $this->item->getKey() . '/deposits',
            $deposit,
        );

        $response->assertForbidden();

        Event::assertNotDispatched(ItemUpdatedQuantity::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        Event::fake(ItemUpdatedQuantity::class);

        $quantity = $this->item->quantity;

        $deposit = [
            'quantity' => 1200000.50,
        ];

        $response = $this->actingAs($this->$user)->postJson(
            "/items/id:{$this->item->getKey()}/deposits",
            $deposit,
        );

        $response
            ->assertCreated()
            ->assertJson([
                'data' => $deposit + [
                    'item_id' => $this->item->getKey(),
                ],
            ]);

        $this->assertDatabaseHas('deposits', ['item_id' => $this->item->getKey()] + $deposit);

        $this->assertDatabaseHas('items', [
            'id' => $this->item->getKey(),
            'quantity' => $quantity + $deposit['quantity'],
        ]);

        Event::assertDispatched(ItemUpdatedQuantity::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWebHook($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemUpdatedQuantity',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Event::fake(ItemUpdatedQuantity::class);

        $deposit = [
            'quantity' => 1200000.50,
        ];

        $response = $this->actingAs($this->$user)->postJson(
            "/items/id:{$this->item->getKey()}/deposits",
            $deposit,
        );

        $response
            ->assertCreated()
            ->assertJson([
                'data' => $deposit + [
                    'item_id' => $this->item->getKey(),
                ],
            ]);

        $this->assertDatabaseHas('deposits', ['item_id' => $this->item->getKey()] + $deposit);

        Event::assertDispatched(ItemUpdatedQuantity::class);

        Bus::fake();

        $item = $this->item;

        $event = new ItemUpdatedQuantity($item);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $item) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $item->getKey()
                && $payload['data_type'] === 'Item'
                && $payload['event'] === 'ItemUpdatedQuantity';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateValidation($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        Event::fake(ItemUpdatedQuantity::class);

        $deposit = [
            'quantity' => 'test',
        ];

        $response = $this->actingAs($this->$user)->postJson(
            "/items/id:{$this->item->getKey()}/deposits",
            $deposit,
        );

        $response->assertStatus(422);

        Event::assertNotDispatched(ItemUpdatedQuantity::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateValidation2($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        Event::fake(ItemUpdatedQuantity::class);

        $deposit = [
            'quantity' => 1000000000000,
        ];

        $response = $this->actingAs($this->$user)->postJson(
            "/items/id:{$this->item->getKey()}/deposits",
            $deposit,
        );

        $response->assertStatus(422);

        Event::assertNotDispatched(ItemUpdatedQuantity::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateValidationInvalidBothShippingTimeAndDate($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        Event::fake(ItemUpdatedQuantity::class);

        $deposit = [
            'quantity' => 1200000.50,
            'shipping_time' => 10,
            'shipping_date' => '1999-02-01 10:10:10',
        ];

        $response = $this->actingAs($this->$user)->postJson(
            "/items/id:{$this->item->getKey()}/deposits",
            $deposit,
        );

        $response->assertStatus(422);

        Event::assertNotDispatched(ItemUpdatedQuantity::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateValidationShippingDateGraterThenItemUnlimitedShippingDate($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        Event::fake(ItemUpdatedQuantity::class);
        $date = Carbon::tomorrow();
        $this->item->unlimited_stock_shipping_date = $date->toDateTimeString();
        $this->item->save();

        $deposit = [
            'quantity' => 1200000.50,
            'shipping_date' => $date->addDays(4)->toDateTimeString(),
        ];

        $response = $this->actingAs($this->$user)->postJson(
            "/items/id:{$this->item->getKey()}/deposits",
            $deposit,
        );

        $response->assertStatus(422);

        Event::assertNotDispatched(ItemUpdatedQuantity::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateShippingDateGraterThenItemUnlimitedShippingDateOlderThenNow($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        Event::fake(ItemUpdatedQuantity::class);
        $date = Carbon::now();
        $this->item->unlimited_stock_shipping_date = $date->addDays(-4)->toDateTimeString();
        $this->item->save();

        $deposit = [
            'quantity' => 1200000.50,
            'shipping_date' => $date->addDays(8)->toDateTimeString(),
        ];

        $response = $this->actingAs($this->$user)->postJson(
            "/items/id:{$this->item->getKey()}/deposits",
            $deposit,
        );

        $response->assertCreated();
        $this->assertDatabaseHas('deposits', $deposit);

        Event::assertDispatched(ItemUpdatedQuantity::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateValidationShippingTimeGraterThenItemUnlimitedShippingTime($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        Event::fake(ItemUpdatedQuantity::class);
        $time = 5;
        $this->item->unlimited_stock_shipping_time = $time;
        $this->item->save();

        $deposit = [
            'quantity' => 1200000.50,
            'shipping_time' => $time + 5,
        ];

        $response = $this->actingAs($this->$user)->postJson(
            "/items/id:{$this->item->getKey()}/deposits",
            $deposit,
        );

        $response->assertStatus(422);

        Event::assertNotDispatched(ItemUpdatedQuantity::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithShippingTime($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        Event::fake(ItemUpdatedQuantity::class);

        $deposit = [
            'quantity' => 1200000.50,
            'shipping_time' => 10,
        ];

        $response = $this->actingAs($this->$user)->postJson(
            "/items/id:{$this->item->getKey()}/deposits",
            $deposit,
        );

        $response->assertCreated();
        $this->assertDatabaseHas('deposits', $deposit);

        Event::assertDispatched(ItemUpdatedQuantity::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithShippingDate($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        Event::fake(ItemUpdatedQuantity::class);

        $deposit = [
            'quantity' => 1200000.50,
            'shipping_date' => '1999-02-01 10:10:10',
        ];

        $response = $this->actingAs($this->$user)->postJson(
            "/items/id:{$this->item->getKey()}/deposits",
            $deposit,
        );

        $response->assertCreated();
        $this->assertDatabaseHas('deposits', $deposit);

        Event::assertDispatched(ItemUpdatedQuantity::class);
    }

    public function testUpdateItemWithShippingTimeAndDateAndCountQuantityNeed(): void
    {
        $depositService = App::make(DepositServiceContract::class);

        $itemData = [
            'unlimited_stock_shipping_date' => Carbon::now()->addDays(10)->toIso8601String(),
        ];

        $item = Item::factory()->create($itemData);

        $this->assertDatabaseHas('items', $itemData);

        $deposit1 = Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_date' => Carbon::now()->addDays(4)->toIso8601String(),
        ]);
        $item->refresh();

        $this->assertEquals($deposit1->shipping_date, $item->shipping_date);
        $this->assertEquals(2, $item->quantity);

        $deposit2 = Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_date' => Carbon::now()->addDays(2)->toIso8601String(),
        ]);
        $item->refresh();

        $this->assertEquals($deposit2->shipping_date, $item->shipping_date);
        $this->assertEquals(4, $item->quantity);

        $deposit3 = Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_date' => Carbon::now()->addDays(6)->toIso8601String(),
        ]);
        $item->refresh();

        $this->assertEquals($deposit2->shipping_date, $item->shipping_date);
        $this->assertEquals(6, $item->quantity);

        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 3.0,
            'shipping_time' => 6,
        ]);
        $item->refresh();

        $this->assertEquals(6, $item->shipping_time);
        $this->assertEquals(9, $item->quantity);

        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 3.0,
            'shipping_time' => 4,
        ]);
        $item->refresh();

        $this->assertEquals(4, $item->shipping_time);
        $this->assertEquals(12, $item->quantity);

        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 3.0,
            'shipping_time' => 8,
        ]);
        $item->refresh();

        $this->assertEquals(4, $item->shipping_time);
        $this->assertEquals(15, $item->quantity);

        $item->update(['unlimited_stock_shipping_time' => 10]);

        /*
        [time = 4 quantity = 3],  sum = 3
         [time = 6 quantity = 3],  sum = 6
         [time = 8 quantity = 3],  sum = 9
         [time = 10, quantity = unlimited],  sum = unlimited  - to remove
        [date = +2 day quantity = 2], after remove unlimited sum = 11
        [date = +4 day quantity = 2], sum = 13
        [date = +6 day quantity = 2], sum =15
        [date = +10 day, quantity = unlimited], sum = unlimited */

        $testCountTimeDate1 = $depositService->getShippingTimeDateForQuantity($item, 2);
        $this->assertEquals(['shipping_time' => 4, 'shipping_date' => null], $testCountTimeDate1);
        $testCountTimeDate2 = $depositService->getShippingTimeDateForQuantity($item, 6);
        $this->assertEquals(['shipping_time' => 6, 'shipping_date' => null], $testCountTimeDate2);
        $testCountTimeDate3 = $depositService->getShippingTimeDateForQuantity($item, 7);
        $this->assertEquals(['shipping_time' => 8, 'shipping_date' => null], $testCountTimeDate3);
        $testCountTimeDate4 = $depositService->getShippingTimeDateForQuantity($item, 20);
        $this->assertEquals(['shipping_time' => 10, 'shipping_date' => null], $testCountTimeDate4);

        $item->update(['unlimited_stock_shipping_time' => null]);

        $testCountTimeDate5 = $depositService->getShippingTimeDateForQuantity($item, 11);
        $this->assertEquals(
            ['shipping_time' => null, 'shipping_date' => $deposit2->shipping_date],
            $testCountTimeDate5
        );
        $testCountTimeDate6 = $depositService->getShippingTimeDateForQuantity($item, 12);
        $this->assertEquals(
            ['shipping_time' => null, 'shipping_date' => $deposit1->shipping_date],
            $testCountTimeDate6
        );
        $testCountTimeDate7 = $depositService->getShippingTimeDateForQuantity($item, 14);
        $this->assertEquals(
            ['shipping_time' => null, 'shipping_date' => $deposit3->shipping_date],
            $testCountTimeDate7
        );
        $testCountTimeDate8 = $depositService->getShippingTimeDateForQuantity($item, 20);
        $this->assertEquals(
            ['shipping_time' => null, 'shipping_date' => $item->unlimited_stock_shipping_date],
            $testCountTimeDate8
        );

        $item->update(['unlimited_stock_shipping_date' => null]);

        $testCountTimeDate9 = $depositService->getShippingTimeDateForQuantity($item, 20);
        $this->assertEquals(['shipping_time' => null, 'shipping_date' => null], $testCountTimeDate9);
    }
}
