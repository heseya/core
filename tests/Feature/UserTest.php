<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    public array $expected;
    private string $validPassword = 'V@l1dPa55word';

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
        $otherUser = User::factory()->create();
        $otherUser->created_at = Carbon::now()->addHour();
        $otherUser->save();

        $response = $this->actingAs($this->user)->getJson('/users');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson(['data' => [
                $this->expected,
                [
                    'id' => $otherUser->getKey(),
                    'email' => $otherUser->email,
                    'name' => $otherUser->name,
                    'avatar' => $otherUser->avatar,
                ],
            ]]);
    }

    public function testIndexSorted(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->created_at = Carbon::now()->addHour();
        $otherUser->save();

        $response = $this->actingAs($this->user)->getJson('/users?sort=created_at:desc');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJson(['data' => [
                [
                    'id' => $otherUser->getKey(),
                    'email' => $otherUser->email,
                    'name' => $otherUser->name,
                    'avatar' => $otherUser->avatar,
                ],
                $this->expected,
            ]]);
    }

    public function testIndexNameSearch(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->user)->getJson('/users?name=' . $user->name);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0', [
                'id' => $user->getKey(),
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => $user->avatar,
            ]);
    }

    public function testIndexEmailSearch(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->user)->getJson('/users?email=' . $user->email);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0', [
                'id' => $user->getKey(),
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => $user->avatar,
            ]);
    }

    public function testIndexFullSearchName(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->user)->getJson('/users?search=' . $user->name);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0', [
                'id' => $user->getKey(),
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => $user->avatar,
            ]);
    }

    public function testIndexFullSearchEmail(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->user)->getJson('/users?search=' . $user->email);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0', [
                'id' => $user->getKey(),
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => $user->avatar,
            ]);
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
        $data = User::factory()->raw() + [
            'password' => $this->validPassword,
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

    public function testCreateTakenEmail(): void
    {
        $data = [
            'name' => User::factory()->raw()['name'],
            'email' => $this->user->email,
            'password' => $this->validPassword,
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
        $data = User::factory()->raw();

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

    public function testUpdateSameEmail(): void
    {
        $response = $this->actingAs($this->user)->patchJson('/users/id:' . $this->user->getKey(), [
            'email' => $this->user->email,
        ]);
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

    public function testUpdateSameName(): void
    {
        $response = $this->actingAs($this->user)->patchJson('/users/id:' . $this->user->getKey(), [
            'name' => $this->user->name,
        ]);
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

    public function testUpdateTakenEmail(): void
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
