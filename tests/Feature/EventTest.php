<?php

namespace Tests\Feature;

use App\Enums\EventType;
use Tests\TestCase;

class EventTest extends TestCase
{
    public function testIndexUnauthorized(): void
    {
        $response = $this->json('GET', '/web-hooks/events');
        $response->assertForbidden();
    }

    public function testIndex(): void
    {
        $this->user->givePermissionTo('events.show');

        $event = EventType::getRandomInstance();

        $response = $this->actingAs($this->user)->json('GET', '/web-hooks/events');
        $response
            ->assertOk()
            ->assertJsonStructure(['data' => [
                0 => [
                    'key',
                    'name',
                    'description',
                ]
            ]])->assertJsonFragment([
                    'key' => $event->value,
                    'description' => __('enums.' . EventType::class . '.' . $event->value),
                ]);
    }
}
