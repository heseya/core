<?php

namespace Tests\Feature;

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
            'model_type' => get_class($this->user),
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
        $this->user->givePermissionTo('webhooks.add', 'orders.show', 'orders.show_details');

        $webHook = WebHook::factory()->create([
            'events' => [
                'OrderCreated'
            ],
            'model_type' => get_class($this->user),
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

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'name' => $webHook->name,
                'url' => $webHook->url,
                'secret' => $webHook->secret,
                'events' => $webHook->events,
                'with_issuer' => $webHook->with_issuer,
                'with_hidden' => $webHook->with_hidden,
                'model_type' => $webHook->model_type,
                'creator_id' => $webHook->creator_id,
            ]);

        $this->assertDatabaseHas('web_hooks', [
            'name' => $webHook->name,
            'url' => $webHook->url,
            'secret' => $webHook->secret,
            'events' => json_encode($webHook->events),
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
            'model_type' => get_class($this->user),
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
            'model_type' => get_class($this->user),
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
}
