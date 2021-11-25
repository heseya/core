<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\WebHook;
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
            'logs' => $this->webHook->logs,
        ];

        $this->expected_structure = [
            'id',
            'name',
            'url',
            'secret',
            'with_issuer',
            'with_hidden',
            'events',
            'logs',
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
        $this->$user->givePermissionTo('webhooks.show');

        $response = $this->actingAs($this->$user)->json('GET', '/webhooks');
        $response
            ->assertOk()
            ->assertJsonStructure(['data' => [
                0 => $this->expected_structure,
            ]])
            ->assertJsonFragment(['data' => [
                0 => $this->expected,
            ]]);
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
        $this->$user->givePermissionTo('webhooks.add', 'products.show', 'products.show_details', 'products.show_hidden');

        $response = $this->actingAs($this->$user)->json('POST', '/webhooks', [
            'name' => 'WebHook test',
            'url' => 'https://www.www.www',
            'secret' => 'secret',
            'events' => [
                'ProductCreated'
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
                    'ProductCreated'
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
            'creator_id' => $this->$user->getKey(),
            'model_type' => $this->$user::class,
        ]);

        $this->assertTrue($this->$user->webhooks->isNotEmpty());
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateNoPermissionToEvent($user): void
    {
        $this->$user->givePermissionTo('webhooks.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
        ]);

        $response = $this->actingAs($this->$user)->json('POST', '/webhooks', [
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
        $this->$user->givePermissionTo('webhooks.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'TestEvent'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
        ]);

        $response = $this->actingAs($this->$user)->json('POST', '/webhooks', [
            'name' => $webHook->name,
            'url' => $webHook->url,
            'secret' => $webHook->secret,
            'events' => $webHook->events,
            'with_issuer' => $webHook->with_issuer,
            'with_hidden' => $webHook->with_hidden,
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
        $this->$user->givePermissionTo('webhooks.edit', 'products.show', 'products.show_details');

        $webHook = WebHook::factory()->for($this->$user, 'hasWebHooks')->create([
            'events' => [
                'OrderCreated'
            ],
            'with_issuer' => false,
            'with_hidden' => true,
        ]);

        $response = $this->actingAs($this->$user)->json('PATCH', '/webhooks/id:' . $webHook->getKey(), [
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
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => $webHook->with_issuer,
            'with_hidden' => false,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateNoPermissionToEvent($user): void
    {
        $this->$user->givePermissionTo('webhooks.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
        ]);

        $response = $this->actingAs($this->$user)->json('PATCH', '/webhooks/id:' . $webHook->getKey(), [
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
                'OrderCreated'
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
                'OrderCreated'
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
        $this->$user->givePermissionTo('webhooks.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated'
            ],
            'model_type' => $this->$user::class,
            'creator_id' => $this->$user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        $response = $this->actingAs($this->$user)->json('DELETE', '/webhooks/id:' . $webHook->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($webHook);
    }

    public function testCannotDeleteAppWebhookByUser(): void
    {
        $this->user->givePermissionTo('webhooks.remove');

        $app = App::factory()->create();

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated'
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
                'OrderCreated'
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
        $this->$user->givePermissionTo('webhooks.show_details');

        $response = $this->actingAs($this->$user)->json('GET', '/webhooks/id:' . $this->webHook->getKey());
        $response
            ->assertOk()
            ->assertJsonFragment($this->expected);
    }
}
