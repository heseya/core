<?php

namespace Tests\Feature;

use App\Enums\EventType;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class EventTest extends TestCase
{
    public function testIndexUnauthorized(): void
    {
        $response = $this->json('GET', '/webhooks/events');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->{$user}->givePermissionTo('events.show');

        $event = EventType::getRandomInstance();

        $hidden_permissions = array_key_exists($event->value, Config::get('events.permissions_hidden'))
            ? Config::get('events.permissions_hidden')[$event->value] : [];

        $response = $this->actingAs($this->{$user})->json('GET', '/webhooks/events');
        $response
            ->assertOk()
            ->assertJsonStructure(['data' => [
                0 => [
                    'key',
                    'name',
                    'description',
                    'required_permissions',
                    'required_hidden_permissions',
                    'encrypted',
                ],
            ],
            ])->assertJsonFragment([
                'key' => $event->value,
                'description' => __('enums.' . EventType::class . '.' . $event->value),
                'required_permissions' => Config::get('events.permissions')[$event->value],
                'required_hidden_permissions' => $hidden_permissions,
                'encrypted' => in_array($event, EventType::SECURED_EVENTS),
            ]);
    }
}
