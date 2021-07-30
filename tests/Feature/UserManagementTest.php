<?php

namespace Tests\Feature;

use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->expected = [
            'id' => $this->user->getKey(),
            'email' => $this->user->email,
            'name' => $this->user->name,
            'avatar' => $this->user->avatar,
        ];
    }

    public function testIndex(): void
    {
        $response = $this->getJson('/users/managements');
        $response->assertUnauthorized();

        $response = $this->actingAs($this->user)->getJson('/users/managements');
        $response
            ->assertOk()
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    public function testView(): void
    {
        $response = $this->getJson('/users/managements/id:' . $this->user->getKey());
        $response->assertUnauthorized();

        $response = $this->actingAs($this->user)->getJson('/users/managements/id:' . $this->user->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => [
                 'id' => $this->user->getKey(),
                 'email' => $this->user->email,
                 'name' => $this->user->name,
                 'avatar' => $this->user->avatar,
             ]]);
    }
}
