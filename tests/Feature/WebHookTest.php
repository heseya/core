<?php

namespace Tests\Feature;

use App\Events\ItemUpdatedQuantity;
use App\Listeners\WebHookEventListener;
use App\Models\App;
use App\Models\Item;
use App\Models\WebHook;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;

class WebHookTest extends TestCase
{
    private WebHook $webHook;

    private array $expected;
    private array $expected_structure;

    public function setUp(): void
    {
        parent::setUp();

        $this->webHook = WebHook::factory()->create([
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
        ]);

        $this->expected = [
            'id' => $this->webHook->getKey(),
            'name' => $this->webHook->name,
            'url' => $this->webHook->url,
            'secret' => $this->webHook->secret,
            'with_issuer' => $this->webHook->with_issuer,
            'with_hidden' => $this->webHook->with_hidden,
            'events' => $this->webHook->events,
        ];

        $this->expected_structure = [
            'id',
            'name',
            'url',
            'secret',
            'with_issuer',
            'with_hidden',
            'events',
        ];
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->json('GET', '/webhooks');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->{$user}->givePermissionTo('webhooks.show');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/webhooks')
            ->assertOk()
            ->assertJsonStructure(['data' => [
                0 => $this->expected_structure,
            ],
            ])
            ->assertJsonFragment(['data' => [
                0 => $this->expected,
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSearch($user): void
    {
        $this->{$user}->givePermissionTo('webhooks.show');

        $webHook = WebHook::factory()->create([
            'name' => 'test webhook',
            'creator_id' => $this->{$user}->getKey(),
            'model_type' => $this->{$user}::class,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/webhooks', [
                'search' => 'test webhook',
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $webHook->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSearchByIds($user): void
    {
        $this->{$user}->givePermissionTo('webhooks.show');

        WebHook::factory()->create([
            'name' => 'test webhook',
            'creator_id' => $this->{$user}->getKey(),
            'model_type' => $this->{$user}::class,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/webhooks', [
                'ids' => [
                    $this->webHook->getKey(),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $this->webHook->getKey()]);
    }

    public function testCreateUnauthorized(): void
    {
        $response = $this->json('POST', '/webhooks');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->{$user}->givePermissionTo(
            'webhooks.add',
            'products.show',
            'products.show_details',
            'products.show_hidden',
        );

        $response = $this->actingAs($this->{$user})->json('POST', '/webhooks', [
            'name' => 'WebHook test',
            'url' => 'https://www.www.www',
            'secret' => 'secret',
            'events' => [
                'ProductCreated',
            ],
            'with_issuer' => false,
            'with_hidden' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'WebHook test',
                'url' => 'https://www.www.www',
                'secret' => 'secret',
                'events' => [
                    'ProductCreated',
                ],
                'with_issuer' => false,
                'with_hidden' => true,
            ]);

        $webHook = WebHook::find($response->getData()->data->id);

        $this->assertEquals(['ProductCreated'], $webHook->events);

        $this->assertDatabaseHas('web_hooks', [
            'name' => $webHook->name,
            'url' => $webHook->url,
            'secret' => $webHook->secret,
            'with_issuer' => $webHook->with_issuer,
            'with_hidden' => $webHook->with_hidden,
            'creator_id' => $this->{$user}->getKey(),
            'model_type' => $this->{$user}::class,
        ]);

        $this->assertTrue($this->{$user}->webhooks->isNotEmpty());
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateNoPermissionToEvent($user): void
    {
        $this->{$user}->givePermissionTo('webhooks.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})->json('POST', '/webhooks', [
            'name' => $webHook->name,
            'url' => $webHook->url,
            'secret' => $webHook->secret,
            'events' => $webHook->events,
            'with_issuer' => $webHook->with_issuer,
            'with_hidden' => $webHook->with_hidden,
        ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateEventNotExist($user): void
    {
        $this->{$user}->givePermissionTo('webhooks.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'TestEvent',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})->json('POST', '/webhooks', [
            'name' => $webHook->name,
            'url' => $webHook->url,
            'secret' => $webHook->secret,
            'events' => $webHook->events,
            'with_issuer' => $webHook->with_issuer,
            'with_hidden' => $webHook->with_hidden,
        ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSecureEventNoSecret($user): void
    {
        $this->{$user}->givePermissionTo('webhooks.add');

        $response = $this->actingAs($this->{$user})->json('POST', '/webhooks', [
            'name' => 'webhook',
            'url' => 'https://example.com',
            'events' => [
                'TfaInit',
            ],
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSecureEventNoSecureUrl($user): void
    {
        $this->{$user}->givePermissionTo('webhooks.add');

        $response = $this->actingAs($this->{$user})->json('POST', '/webhooks', [
            'name' => 'webhook',
            'url' => 'http://example.com',
            'events' => [
                'TfaInit',
            ],
            'secret' => 'secret',
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        $response->assertStatus(422);
    }

    public function testUpdateUnauthorized(): void
    {
        $response = $this->json('PATCH', '/webhooks/id:' . $this->webHook->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->{$user}->givePermissionTo('webhooks.edit', 'products.show', 'products.show_details');

        $webHook = WebHook::factory()->for($this->{$user}, 'hasWebHooks')->create([
            'events' => [
                'OrderCreated',
            ],
            'with_issuer' => false,
            'with_hidden' => true,
        ]);

        $response = $this->actingAs($this->{$user})->json('PATCH', '/webhooks/id:' . $webHook->getKey(), [
            'name' => 'Update test',
            'events' => [
                'ProductCreated',
            ],
            'with_hidden' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Update test',
                'url' => $webHook->url,
                'secret' => $webHook->secret,
                'events' => [
                    'ProductCreated',
                ],
                'with_issuer' => $webHook->with_issuer,
                'with_hidden' => false,
            ]);

        $webHookDB = WebHook::find($webHook->getKey());

        $this->assertEquals(['ProductCreated'], $webHookDB->events);

        $this->assertDatabaseHas('web_hooks', [
            'id' => $webHook->getKey(),
            'name' => 'Update test',
            'url' => $webHook->url,
            'secret' => $webHook->secret,
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => $webHook->with_issuer,
            'with_hidden' => false,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateNoPermissionToEvent($user): void
    {
        $this->{$user}->givePermissionTo('webhooks.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})->json('PATCH', '/webhooks/id:' . $webHook->getKey(), [
            'name' => $webHook->name,
            'url' => $webHook->url,
            'secret' => $webHook->secret,
            'events' => $webHook->events,
            'with_issuer' => $webHook->with_issuer,
            'with_hidden' => $webHook->with_hidden,
        ]);

        $response->assertStatus(422);
    }

    public function testCannotUpdateAppWebhookByUser(): void
    {
        $this->user->givePermissionTo('webhooks.edit', 'products.show', 'products.show_details');

        $app = App::factory()->create();

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated',
            ],
            'model_type' => App::class,
            'creator_id' => $app->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        $response = $this->actingAs($this->user)->json('PATCH', '/webhooks/id:' . $webHook->getKey(), [
            'name' => 'Update test',
            'events' => [
                'ProductCreated',
            ],
        ]);

        $response->assertStatus(403);
    }

    public function testCannotUpdateUserWebhookByApp(): void
    {
        $app = App::factory()->create();

        $app->givePermissionTo('webhooks.edit', 'products.show', 'products.show_details');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        $response = $this->actingAs($app)->json('PATCH', '/webhooks/id:' . $webHook->getKey(), [
            'name' => 'Update test',
            'events' => [
                'ProductCreated',
            ],
        ]);

        $response->assertStatus(403);
    }

    public function testDeleteUnauthorized(): void
    {
        $response = $this->json('DELETE', '/webhooks/id:' . $this->webHook->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->{$user}->givePermissionTo('webhooks.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        $response = $this->actingAs($this->{$user})->json('DELETE', '/webhooks/id:' . $webHook->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($webHook);
    }

    public function testCannotDeleteAppWebhookByUser(): void
    {
        $this->user->givePermissionTo('webhooks.remove');

        $app = App::factory()->create();

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated',
            ],
            'model_type' => App::class,
            'creator_id' => $app->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        $response = $this->actingAs($this->user)->json('DELETE', '/webhooks/id:' . $webHook->getKey());
        $response->assertStatus(403);
    }

    public function testCannotDeleteUserWebhookByApp(): void
    {
        $app = App::factory()->create();

        $app->givePermissionTo('webhooks.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        $response = $this->actingAs($app)->json('DELETE', '/webhooks/id:' . $webHook->getKey());
        $response->assertStatus(403);
    }

    public function testShowUnauthorized(): void
    {
        $response = $this->json('GET', '/webhooks/id:' . $this->webHook->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow($user): void
    {
        $this->{$user}->givePermissionTo('webhooks.show_details');

        $response = $this->actingAs($this->{$user})->json('GET', '/webhooks/id:' . $this->webHook->getKey());
        $response
            ->assertOk()
            ->assertJsonFragment($this->expected);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWrongId($user): void
    {
        $this->{$user}->givePermissionTo('webhooks.show_details');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/webhooks/id:its-not-uuid')
            ->assertNotFound();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/webhooks/id:' . $this->webHook->getKey() . $this->webHook->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testWebHookHasApiUrl($user): void
    {
        $this->{$user}->givePermissionTo('deposits.add');

        $item = Item::factory()->create();

        WebHook::factory()->create([
            'events' => [
                'ItemUpdatedQuantity',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        $deposit = [
            'quantity' => 1200000.50,
        ];

        $this->actingAs($this->{$user})->postJson(
            "/items/id:{$item->getKey()}/deposits",
            $deposit,
        );

        Bus::fake();

        $event = new ItemUpdatedQuantity($item);
        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, fn ($job) => $job->payload['api_url'] === Config::get('app.url'));
    }
}
