<?php

namespace Tests\Feature;

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
                    'key' => 'DiscountDeleted',
                    'name' => 'Discount deleted',
                    'description' => __('enums.App\Enums\EventPermissionType.discount_deleted'),
                ]);
    }
}
