<?php

namespace Tests\Feature\Items;

use App\Enums\ValidationError;
use App\Events\ItemUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\WebHook;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookServer\CallWebhookJob;

class ItemUpdateTest extends ItemTestCase
{
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
}
