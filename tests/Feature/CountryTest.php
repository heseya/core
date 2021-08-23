<?php

namespace Tests\Feature;

use Tests\TestCase;

class CountryTest extends TestCase
{
    public function testIndexUnauthorized(): void
    {
        $response = $this->actingAs($this->user)->getJson('/countries');

        $response->assertForbidden();
    }

    public function testIndex(): void
    {
        $this->user->givePermissionTo('countries.show');
        $response = $this->actingAs($this->user)->getJson('/countries');

        $response->assertOk();
    }
}
