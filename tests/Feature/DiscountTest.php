<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Events\DiscountCreated;
use App\Events\DiscountDeleted;
use App\Events\DiscountUpdated;
use App\Listeners\WebHookEventListener;
use App\Models\Discount;
use App\Models\WebHook;
use Carbon\Carbon;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class DiscountTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Discount::factory()->count(10)->create();
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/discounts');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('discounts.show');

        $this
            ->actingAs($this->$user)
            ->getJson('/discounts')
            ->assertOk()
            ->assertJsonCount(10, 'data');

        $this->assertQueryCountLessThan(15);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexPerformance($user): void
    {
        $this->$user->givePermissionTo('discounts.show');

        Discount::factory()->count(490)->create();

        $this
            ->actingAs($this->$user)
            ->getJson('/discounts?limit=500')
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(15);
    }

    public function testShowUnauthorized(): void
    {
        $discount = Discount::factory()->create();

        $response = $this->getJson('/discounts/' . $discount->code);
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow($user): void
    {
        $this->$user->givePermissionTo('discounts.show_details');
        $discount = Discount::factory()->create();

        $response = $this->actingAs($this->$user)->getJson('/discounts/' . $discount->code);
        $response
            ->assertOk()
            ->assertJsonFragment(['id' => $discount->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWrongCode($user): void
    {
        $this->$user->givePermissionTo('discounts.show_details');
        $discount = Discount::factory()->create();

        $this
            ->actingAs($this->$user)
            ->getJson('/discounts/its_not_code')
            ->assertNotFound();

        $this
            ->actingAs($this->$user)
            ->getJson('/discounts/' . $discount->code . '_' . $discount->code)
            ->assertNotFound();
    }

    public function testCreateUnauthorized(): void
    {
        Event::fake();

        $response = $this->postJson('/discounts');
        $response->assertForbidden();

        Event::assertNotDispatched(DiscountCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('discounts.add');

        Queue::fake();

        $response = $this->actingAs($this->$user)->json('POST', '/discounts', [
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'type' => DiscountType::PERCENTAGE,
            'max_uses' => 20,
            'starts_at' => Carbon::yesterday()->format('Y-m-d\TH:i'),
            'expires_at' => Carbon::tomorrow()->format('Y-m-d\TH:i'),
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'description' => 'Testowy kupon',
                'code' => 'S43SA2',
                'discount' => 10,
                'type' => DiscountType::PERCENTAGE,
                'max_uses' => 20,
                'uses' => 0,
                'available' => true,
                'starts_at' => Carbon::yesterday(),
                'expires_at' => Carbon::tomorrow(),
                'metadata' => [],
            ]);

        $this->assertDatabaseHas('discounts', [
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'max_uses' => 20,
            'type' => DiscountType::PERCENTAGE,
            'starts_at' => Carbon::yesterday(),
            'expires_at' => Carbon::tomorrow(),
        ]);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof DiscountCreated;
        });

        $discount = Discount::find($response->getData()->data->id);
        $event = new DiscountCreated($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWebHookQueue($user): void
    {
        $this->$user->givePermissionTo('discounts.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'DiscountCreated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->$user)->json('POST', '/discounts', [
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'type' => DiscountType::PERCENTAGE,
            'max_uses' => 20,
            'starts_at' => Carbon::yesterday()->format('Y-m-d\TH:i'),
            'expires_at' => Carbon::tomorrow()->format('Y-m-d\TH:i'),
        ]);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof DiscountCreated;
        });

        $discount = Discount::find($response->getData()->data->id);
        $event = new DiscountCreated($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertPushed(CallWebhookJob::class, function ($job) use ($webHook, $discount) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $discount->getKey()
                && $payload['data_type'] === 'Discount'
                && $payload['event'] === 'DiscountCreated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithWebHookDispatched($user): void
    {
        $this->$user->givePermissionTo('discounts.add', 'users.show', 'apps.show');

        $webHook = WebHook::factory()->create([
            'events' => [
                'DiscountCreated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->$user)->json('POST', '/discounts', [
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'type' => DiscountType::PERCENTAGE,
            'max_uses' => 20,
            'starts_at' => Carbon::yesterday()->format('Y-m-d\TH:i'),
            'expires_at' => Carbon::tomorrow()->format('Y-m-d\TH:i'),
        ]);

        $response->assertCreated();

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof DiscountCreated;
        });

        $discount = Discount::find($response->getData()->data->id);

        $event = new DiscountCreated($discount);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $discount) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $discount->getKey()
                && $payload['data_type'] === 'Discount'
                && $payload['event'] === 'DiscountCreated';
        });
    }

    public function testUpdateUnauthorized(): void
    {
        $discount = Discount::factory()->create();

        Event::fake();

        $response = $this->patchJson('/discounts/id:' .  $discount->getKey());
        $response->assertForbidden();

        Event::assertNotDispatched(DiscountUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->$user->givePermissionTo('discounts.edit');
        $discount = Discount::factory()->create();

        Queue::fake();

        $response = $this->actingAs($this->$user)
            ->json('PATCH', '/discounts/id:' . $discount->getKey(), [
                'description' => 'Weekend Sale',
                'code' => 'WEEKEND',
                'discount' => 20,
                'type' => DiscountType::AMOUNT,
                'max_uses' => 40,
                'starts_at' => Carbon::yesterday()->format('Y-m-d\TH:i'),
                'expires_at' => Carbon::tomorrow()->format('Y-m-d\TH:i'),
            ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $discount->getKey(),
                'description' => 'Weekend Sale',
                'code' => 'WEEKEND',
                'discount' => 20,
                'type' => DiscountType::AMOUNT,
                'starts_at' => Carbon::yesterday(),
                'expires_at' => Carbon::tomorrow(),
                'metadata' => [],
            ]);

        $this->assertDatabaseHas('discounts', [
            'id' => $discount->getKey(),
            'description' => 'Weekend Sale',
            'code' => 'WEEKEND',
            'discount' => 20,
            'type' => DiscountType::AMOUNT,
            'max_uses' => 40,
            'starts_at' => Carbon::yesterday(),
            'expires_at' => Carbon::tomorrow(),
        ]);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof DiscountUpdated;
        });

        $discount = Discount::find($discount->getKey());
        $event = new DiscountUpdated($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithPartialData($user): void
    {
        $this->$user->givePermissionTo('discounts.edit');
        $discount = Discount::factory()->create();

        Queue::fake();

        $response = $this->actingAs($this->$user)
            ->json('PATCH', '/discounts/id:' . $discount->getKey(), [
                'max_uses' => 40,
                'starts_at' => Carbon::yesterday()->format('Y-m-d\TH:i'),
                'expires_at' => Carbon::tomorrow()->format('Y-m-d\TH:i'),
            ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $discount->getKey(),
                'description' => $discount->description,
                'code' => $discount->code,
                'discount' => $discount->discount,
                'type' => $discount->type,
                'max_uses' => 40,
                'starts_at' => Carbon::yesterday(),
                'expires_at' => Carbon::tomorrow(),
                'metadata' => [],
            ]);

        $this->assertDatabaseHas('discounts', [
            'id' => $discount->getKey(),
            'description' => $discount->description,
            'code' => $discount->code,
            'discount' => $discount->discount,
            'type' => $discount->type,
            'max_uses' => 40,
            'starts_at' => Carbon::yesterday(),
            'expires_at' => Carbon::tomorrow(),
        ]);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithWebHookQueue($user): void
    {
        $this->$user->givePermissionTo('discounts.edit');
        $discount = Discount::factory()->create();

        $webHook = WebHook::factory()->create([
            'events' => [
                'DiscountUpdated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Queue::fake();

        $this
            ->actingAs($this->$user)
            ->json('PATCH', '/discounts/id:' . $discount->getKey(), [
                'description' => 'Weekend Sale',
                'code' => 'WEEKEND',
                'discount' => 20,
                'type' => DiscountType::AMOUNT,
                'max_uses' => 40,
                'starts_at' => Carbon::yesterday()->format('Y-m-d\TH:i'),
                'expires_at' => Carbon::tomorrow()->format('Y-m-d\TH:i'),
            ]);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof DiscountUpdated;
        });

        $discount = Discount::find($discount->getKey());
        $event = new DiscountUpdated($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertPushed(CallWebhookJob::class, function ($job) use ($webHook, $discount) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $discount->getKey()
                && $payload['data_type'] === 'Discount'
                && $payload['event'] === 'DiscountUpdated';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithWebHookDispatched($user): void
    {
        $this->$user->givePermissionTo('discounts.edit');
        $discount = Discount::factory()->create();

        $webHook = WebHook::factory()->create([
            'events' => [
                'DiscountUpdated',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $this
            ->actingAs($this->$user)
            ->json('PATCH', '/discounts/id:' . $discount->getKey(), [
                'description' => 'Weekend Sale',
                'code' => 'WEEKEND',
                'discount' => 20,
                'type' => DiscountType::AMOUNT,
                'max_uses' => 40,
                'starts_at' => Carbon::yesterday()->format('Y-m-d\TH:i'),
                'expires_at' => Carbon::tomorrow()->format('Y-m-d\TH:i'),
            ]);

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof DiscountUpdated;
        });

        $discount = Discount::find($discount->getKey());
        $event = new DiscountUpdated($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $discount) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $discount->getKey()
                && $payload['data_type'] === 'Discount'
                && $payload['event'] === 'DiscountUpdated';
        });
    }

    public function testDeleteUnauthorized(): void
    {
        $discount = Discount::factory()->create();

        Event::fake();

        $response = $this->deleteJson('/discounts/id:' . $discount->getKey());
        $response->assertForbidden();

        Event::assertNotDispatched(DiscountDeleted::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('discounts.remove');
        $discount = Discount::factory()->create();

        Queue::fake();

        $response = $this->actingAs($this->$user)->deleteJson('/discounts/id:' . $discount->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($discount);

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof DiscountDeleted;
        });

        $event = new DiscountDeleted($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithWebHookQueue($user): void
    {
        $this->$user->givePermissionTo('discounts.remove');
        $discount = Discount::factory()->create();

        $webHook = WebHook::factory()->create([
            'events' => [
                'DiscountDeleted',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->$user)->deleteJson('/discounts/id:' . $discount->getKey());

        Queue::assertPushed(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof DiscountDeleted;
        });

        $response->assertNoContent();
        $this->assertSoftDeleted($discount);

        $event = new DiscountDeleted($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertPushed(CallWebhookJob::class, function ($job) use ($webHook, $discount) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $discount->getKey()
                && $payload['data_type'] === 'Discount'
                && $payload['event'] === 'DiscountDeleted';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteWithWebHookDispatched($user): void
    {
        $this->$user->givePermissionTo('discounts.remove');
        $discount = Discount::factory()->create();

        $webHook = WebHook::factory()->create([
            'events' => [
                'DiscountDeleted',
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->$user)->deleteJson('/discounts/id:' . $discount->getKey());

        Bus::assertDispatched(CallQueuedListener::class, function ($job) {
            return $job->class === WebHookEventListener::class
                && $job->data[0] instanceof DiscountDeleted;
        });

        $response->assertNoContent();
        $this->assertSoftDeleted($discount);

        $event = new DiscountDeleted($discount);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $discount) {
            $payload = $job->payload;
            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $discount->getKey()
                && $payload['data_type'] === 'Discount'
                && $payload['event'] === 'DiscountDeleted';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateCheckDatetime($user): void
    {
        $this->$user->givePermissionTo('discounts.add');

        Event::fake([DiscountCreated::class]);

        $response = $this->actingAs($this->$user)->json('POST', '/discounts', [
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'type' => DiscountType::PERCENTAGE,
            'max_uses' => 20,
            'starts_at' => '2021-09-20T12:00',
            'expires_at' => '2021-09-21T12:00',
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'description' => 'Testowy kupon',
                'code' => 'S43SA2',
                'discount' => 10,
                'type' => DiscountType::PERCENTAGE,
                'max_uses' => 20,
                'uses' => 0,
                'starts_at' => '2021-09-20T12:00:00.000000Z',
                'expires_at' => '2021-09-21T12:00:00.000000Z',
                'metadata' => [],
            ]);

        $this->assertDatabaseHas('discounts', [
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'max_uses' => 20,
            'type' => DiscountType::PERCENTAGE,
            'starts_at' => '2021-09-20T12:00',
            'expires_at' => '2021-09-21T12:00',
        ]);

        Event::assertDispatched(DiscountCreated::class);
    }
}
