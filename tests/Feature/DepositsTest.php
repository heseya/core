<?php

namespace Tests\Feature;

use App\Events\ItemUpdatedQuantity;
use App\Listeners\WebHookEventListener;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\WebHook;
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

    public function testIndex(): void
    {
        $this->user->givePermissionTo('deposits.show');

        $response = $this->actingAs($this->user)->getJson('/deposits');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    public function testViewUnauthorized(): void
    {
        $response = $this->getJson('/items/id:' . $this->item->getKey() . '/deposits');
        $response->assertForbidden();
    }

    public function testView(): void
    {
        $this->user->givePermissionTo('deposits.show');

        $response = $this->actingAs($this->user)
            ->getJson('/items/id:' . $this->item->getKey() . '/deposits');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
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

    public function testCreate(): void
    {
        $this->user->givePermissionTo('deposits.add');

        Event::fake(ItemUpdatedQuantity::class);

        $deposit = [
            'quantity' => 1200000.50,
        ];

        $response = $this->actingAs($this->user)->postJson(
            "/items/id:{$this->item->getKey()}/deposits",
            $deposit,
        );

        $response
            ->assertCreated()
            ->assertJson(['data' => $deposit + [
                'item_id' => $this->item->getKey(),
            ]]);

        $this->assertDatabaseHas('deposits', ['item_id' => $this->item->getKey()] + $deposit);

        Event::assertDispatched(ItemUpdatedQuantity::class);
    }

    public function testCreateWithWebHook(): void
    {
        $this->user->givePermissionTo('deposits.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemUpdatedQuantity'
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

        $response = $this->actingAs($this->user)->postJson(
            "/items/id:{$this->item->getKey()}/deposits",
            $deposit,
        );

        $response
            ->assertCreated()
            ->assertJson(['data' => $deposit + [
                    'item_id' => $this->item->getKey(),
                ]]);

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
                && $job->headers['X-Heseya-Token'] === $webHook->secret
                && $payload['data']['id'] === $item->getKey()
                && $payload['data_type'] === 'Item'
                && $payload['event'] === 'ItemUpdatedQuantity';
        });
    }

    public function testCreateValidation(): void
    {
        $this->user->givePermissionTo('deposits.add');

        Event::fake(ItemUpdatedQuantity::class);

        $deposit = [
            'quantity' => 'test',
        ];

        $response = $this->actingAs($this->user)->postJson(
            "/items/id:{$this->item->getKey()}/deposits",
            $deposit,
        );

        $response->assertStatus(422);

        Event::assertNotDispatched(ItemUpdatedQuantity::class);
    }

    public function testCreateValidation2(): void
    {
        $this->user->givePermissionTo('deposits.add');

        Event::fake(ItemUpdatedQuantity::class);

        $deposit = [
            'quantity' => 1000000000000,
        ];

        $response = $this->actingAs($this->user)->postJson(
            "/items/id:{$this->item->getKey()}/deposits",
            $deposit,
        );

        $response->assertStatus(422);

        Event::assertNotDispatched(ItemUpdatedQuantity::class);
    }
}
