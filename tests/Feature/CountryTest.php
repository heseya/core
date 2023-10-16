<?php

namespace Tests\Feature;

use Tests\TestCase;

class CountryTest extends TestCase
{
    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/countries');

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->{$user}->givePermissionTo('countries.show');

        $this
            ->actingAs($this->{$user})
            ->getJson('/countries')
            ->assertOk();
    }
}
