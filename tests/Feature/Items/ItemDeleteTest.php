<?php

namespace Tests\Feature\Items;

use App\Events\ItemDeleted;
use App\Listeners\WebHookEventListener;
use App\Models\WebHook;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookServer\CallWebhookJob;

class ItemDeleteTest extends ItemTestCase
{
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
}
