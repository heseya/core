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

        $this->webHook = WebHook::factory()->create();

        $this->expected = [
            'id' => $this->webHook->getKey(),
            'name' => $this->webHook->name,
            'url' => $this->webHook->url,
            'with_issuer' => $this->webHook->with_issuer,
            'with_hidden' => $this->webHook->with_hidden,
            'events' => $this->webHook->events,
            'logs' => $this->webHook->logs,
        ];

        $this->expected_structure = [
            'id',
            'name',
            'url',
            'with_issuer',
            'with_hidden',
            'events',
            'logs',
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
}
