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
            'model_type' => $this->webHook->model_type,
            'creator_id' => $this->webHook->creator_id,
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
            'model_type',
            'creator_id',
        ];
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->json('GET', '/web-hooks');
        $response->assertForbidden();
    }

    public function testIndex(): void
    {
        $this->user->givePermissionTo('webhooks.show');

        $response = $this->actingAs($this->user)->json('GET', '/web-hooks');
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
        $response = $this->json('POST', '/web-hooks');
        $response->assertForbidden();
    }

    public function testCreate(): void
    {
        $this->user->givePermissionTo('webhooks.add', 'products.show', 'products.show_details', 'products.show_hidden');

        $response = $this->actingAs($this->user)->json('POST', '/web-hooks', [
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
                'model_type' => $this->user::class,
                'creator_id' => $this->user->getKey(),
            ]);

        $webHook = WebHook::find($response->getData()->data->id);

        $this->assertEquals(['ProductCreated'], $webHook->events);

        $this->assertDatabaseHas('web_hooks', [
            'name' => $webHook->name,
            'url' => $webHook->url,
            'secret' => $webHook->secret,
            'with_issuer' => $webHook->with_issuer,
            'with_hidden' => $webHook->with_hidden,
            'model_type' => $webHook->model_type,
            'creator_id' => $webHook->creator_id,
        ]);
    }

    public function testCreateNoPermissionToEvent(): void
    {
        $this->user->givePermissionTo('webhooks.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated'
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
        ]);

        $response = $this->actingAs($this->user)->json('POST', '/web-hooks', [
            'name' => $webHook->name,
            'url' => $webHook->url,
            'secret' => $webHook->secret,
            'events' => $webHook->events,
            'with_issuer' => $webHook->with_issuer,
            'with_hidden' => $webHook->with_hidden,
        ]);

        $response->assertStatus(422);
    }

    public function testCreateEventNotExist(): void
    {
        $this->user->givePermissionTo('webhooks.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'TestEvent'
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
        ]);

        $response = $this->actingAs($this->user)->json('POST', '/web-hooks', [
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
        $response = $this->json('PATCH', '/web-hooks/id:' . $this->webHook->getKey());
        $response->assertForbidden();
    }

    public function testUpdateByUser(): void
    {
        $this->user->givePermissionTo('webhooks.edit', 'products.show', 'products.show_details');

        $webHook = WebHook::factory()->for($this->user, 'hasWebHooks')->create([
            'events' => [
                'OrderCreated'
            ],
            'with_issuer' => false,
            'with_hidden' => true,
        ]);

        $response = $this->actingAs($this->user)->json('PATCH', '/web-hooks/id:' . $webHook->getKey(), [
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
                'model_type' => $this->user::class,
                'creator_id' => $this->user->getKey(),
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
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => $webHook->with_issuer,
            'with_hidden' => false,
        ]);
    }

    public function testUpdateByApp(): void
    {
        $app = App::factory()->create();
        $app->givePermissionTo('webhooks.edit', 'products.show', 'products.show_details');

        $webHook = WebHook::factory()->for($app, 'hasWebHooks')->create([
            'events' => [
                'OrderCreated'
            ],
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        $response = $this->actingAs($app)->json('PATCH', '/web-hooks/id:' . $webHook->getKey(), [
            'name' => 'Update test',
            'events' => [
                'ProductCreated',
            ],
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
                'model_type' => $webHook->model_type,
                'creator_id' => $webHook->creator_id,
                'with_issuer' => $webHook->with_issuer,
                'with_hidden' => $webHook->with_hidden,
            ]);

        $webHookDB = WebHook::find($webHook->getKey());

        $this->assertEquals(['ProductCreated'], $webHookDB->events);

        $this->assertDatabaseHas('web_hooks', [
            'id' => $webHook->getKey(),
            'name' => 'Update test',
            'url' => $webHook->url,
            'secret' => $webHook->secret,
            'model_type' => $webHook->model_type,
            'creator_id' => $webHook->creator_id,
            'with_issuer' => $webHook->with_issuer,
            'with_hidden' => $webHook->with_hidden,
        ]);
    }

    public function testUpdateNoPermissionToEvent(): void
    {
        $this->user->givePermissionTo('webhooks.edit');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated'
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
        ]);

        $response = $this->actingAs($this->user)->json('PATCH', '/web-hooks/id:' . $webHook->getKey(), [
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

        $response = $this->actingAs($this->user)->json('PATCH', '/web-hooks/id:' . $webHook->getKey(), [
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

        $response = $this->actingAs($app)->json('PATCH', '/web-hooks/id:' . $webHook->getKey(), [
            'name' => 'Update test',
            'events' => [
                'ProductCreated',
            ],
        ]);

        $response->assertStatus(403);
    }

    public function testDeleteUnauthorized(): void
    {
        $response = $this->json('DELETE', '/web-hooks/id:' . $this->webHook->getKey());
        $response->assertForbidden();
    }

    public function testDeleteByUser(): void
    {
        $this->user->givePermissionTo('webhooks.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated'
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        $response = $this->actingAs($this->user)->json('DELETE', '/web-hooks/id:' . $webHook->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($webHook);
    }

    public function testDeleteByApp(): void
    {
        $app = App::factory()->create();
        $app->givePermissionTo('webhooks.remove');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated'
            ],
            'model_type' => $app::class,
            'creator_id' => $app->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        $response = $this->actingAs($app)->json('DELETE', '/web-hooks/id:' . $webHook->getKey());
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

        $response = $this->actingAs($this->user)->json('DELETE', '/web-hooks/id:' . $webHook->getKey());
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

        $response = $this->actingAs($app)->json('DELETE', '/web-hooks/id:' . $webHook->getKey());
        $response->assertStatus(403);
    }

    public function testShowUnauthorized(): void
    {
        $response = $this->json('GET', '/web-hooks/id:' . $this->webHook->getKey());
        $response->assertForbidden();
    }

    public function testShow(): void
    {
        $this->user->givePermissionTo('webhooks.show_details');

        $response = $this->actingAs($this->user)->json('GET', '/web-hooks/id:' . $this->webHook->getKey());
        $response
            ->assertOk()
            ->assertJsonFragment($this->expected);
    }
}
