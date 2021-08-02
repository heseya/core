<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use WithFaker;

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

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/users/managements');
        $response->assertUnauthorized();
    }

    public function testIndex(): void
    {
        $response = $this->actingAs($this->user)->getJson('/users/managements');
        $response
            ->assertOk()
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    public function testShowUnauthorized(): void
    {
        $response = $this->getJson('/users/managements/id:' . $this->user->getKey());
        $response->assertUnauthorized();
    }

    public function testShow(): void
    {
        $response = $this->actingAs($this->user)->getJson('/users/managements/id:' . $this->user->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected]);
    }

    public function testCreateUnauthorized(): void
    {
        $response = $this->getJson('/users/managements');
        $response->assertUnauthorized();
    }

    public function testCreate(): void
    {
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'password' => $this->faker->password(10),
        ];

        $response = $this->actingAs($this->user)->postJson('/users/managements', $data);
        $response
            ->assertCreated()
            ->assertJson(['data' =>
                  [
                      'id' => $response->getData()->data->id,
                      'email' => $data['email'],
                      'name' => $data['name'],
                      'avatar' => $response->getData()->data->avatar,
                  ]
             ]);

        $this->assertDatabaseHas('users', [
            'id' => $response->getData()->data->id,
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }

    public function testCreateByBusyEmail(): void
    {
        $data = [
            'name' => $this->faker->name,
            'email' => $this->user->email,
            'password' => $this->faker->password(10),
        ];

        $response = $this->actingAs($this->user)->postJson('/users/managements', $data);
        $response->assertStatus(422);
    }

    public function testUpdateUnauthorized(): void
    {
        $response = $this->patchJson('/users/managements/id:' . $this->user->getKey());
        $response->assertUnauthorized();
    }

    public function testUpdate(): void
    {
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/users/managements/id:' . $this->user->getKey(),
            $data,
        );

        $response
            ->assertOk()
            ->assertJson(['data' =>
                  [
                      'id' => $this->user->getKey(),
                      'email' => $data['email'],
                      'name' => $data['name'],
                      'avatar' => $response->getData()->data->avatar,
                  ]
             ]);

        $this->user = User::find($this->user->getKey());
        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'name' => $this->user->name,
            'email' => $this->user->email,
        ]);
    }

    public function testUpdateByEmptyData(): void
    {
        $response = $this->actingAs($this->user)->patchJson(
            '/users/managements/id:' . $this->user->getKey(),
            [],
        );

        $response
            ->assertOk()
            ->assertJson(['data' =>
                  [
                      'id' => $this->user->getKey(),
                      'email' => $this->user->email,
                      'name' => $this->user->name,
                      'avatar' => $response->getData()->data->avatar,
                  ]
             ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),

            // should remain the same
            'name' => $this->user->name,
            'email' => $this->user->email,
        ]);
    }

    public function testUpdateByBusyEmail(): void
    {
        $user = User::factory()->count(3)->create();

        $data = [
            'email' => $user[1]->email,
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/users/managements/id:' . $this->user->getKey(),
            $data,
        );
        $response->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'email' => $this->user->email,
        ]);
    }

    public function testDeleteUnauthorized(): void
    {
        $response = $this->deleteJson('/users/managements/id:' . $this->user->getKey());
        $response->assertUnauthorized();
    }

    public function testDelete(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/users/managements/id:' . $this->user->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($this->user);
    }
}
