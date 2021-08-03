<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
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
        $response = $this->getJson('/users');
        $response->assertUnauthorized();
    }

    public function testIndex(): void
    {
        $other = User::factory()->create();

        $response = $this->actingAs($this->user)->getJson('/users');
        $response
            ->assertOk()
            ->assertJson(['data' => [
                0 => $this->expected,
                1 => [
                    'id' => $other->getKey(),
                    'email' => $other->email,
                    'name' => $other->name,
                    'avatar' => $other->avatar,
                ],
            ]]);
    }

    public function testShowUnauthorized(): void
    {
        $response = $this->getJson('/users/id:' . $this->user->getKey());
        $response->assertUnauthorized();
    }

    public function testShow(): void
    {
        $response = $this->actingAs($this->user)->getJson('/users/id:' . $this->user->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected]);
    }

    public function testCreateUnauthorized(): void
    {
        $response = $this->getJson('/users');
        $response->assertUnauthorized();
    }

    public function testCreate(): void
    {
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'password' => $this->faker->password(10),
        ];

        $response = $this->actingAs($this->user)->postJson('/users', $data);
        $response
            ->assertCreated()
            ->assertJsonPath('data.email', $data['email'])
            ->assertJsonPath('data.name', $data['name']);

        $userId = $response->getData()->data->id;

        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        $user = User::find($userId);
        $this->assertTrue(Hash::check($data['password'], $user->password));
    }

    public function testCreateByBusyEmail(): void
    {
        $data = [
            'name' => $this->faker->name,
            'email' => $this->user->email,
            'password' => $this->faker->password(10),
        ];

        $response = $this->actingAs($this->user)->postJson('/users', $data);
        $response->assertStatus(422);
    }

    public function testUpdateUnauthorized(): void
    {
        $response = $this->patchJson('/users/id:' . $this->user->getKey());
        $response->assertUnauthorized();
    }

    public function testUpdate(): void
    {
        $data = [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/users/id:' . $this->user->getKey(),
            $data,
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $this->user->getKey())
            ->assertJsonPath('data.email', $data['email'])
            ->assertJsonPath('data.name', $data['name']);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
    }

    public function testUpdateByEmptyData(): void
    {
        $response = $this->actingAs($this->user)->patchJson('/users/id:' . $this->user->getKey());
        $response
            ->assertOk()
            ->assertJsonPath('data.id', $this->user->getKey())
            ->assertJsonPath('data.email', $this->user->email)
            ->assertJsonPath('data.name', $this->user->name);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'name' => $this->user->name,
            'email' => $this->user->email,
        ]);
    }

    public function testUpdateByBusyEmail(): void
    {
        $other = User::factory()->create();

        $response = $this->actingAs($this->user)->patchJson('/users/id:' . $this->user->getKey(), [
            'email' => $other->email,
        ]);
        $response->assertStatus(422);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'email' => $this->user->email,
        ]);
    }

    public function testDeleteUnauthorized(): void
    {
        $response = $this->deleteJson('/users/id:' . $this->user->getKey());
        $response->assertUnauthorized();
    }

    public function testDelete(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/users/id:' . $this->user->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($this->user);
    }
}
