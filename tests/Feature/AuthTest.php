<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function testLogin(): void
    {
        $response = $this->postJson('/login', [
            'email' => $this->user->email,
            'password' => $this->password,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'token',
                'expires_at',
                'user' => [
                    'id',
                    'email',
                    'name',
                    'avatar',
                ],
                'scopes' => [],
            ]]);
    }

    public function testLogout(): void
    {
        $response = $this->actingAs($this->user)->postJson('/auth/logout');
        $response->assertNoContent();
    }

    public function testChangePassword(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('test'),
        ]);

        $response = $this->actingAs($user)->patchJson('/user/password', [
            'password' => 'test',
            'password_new' => 'test123456',
        ]);

        $response->assertNoContent();

        $user->refresh();
        $this->assertTrue(Hash::check('test123456', $user->password));
    }

    public function testLoginHistory(): void
    {
        $response = $this->actingAs($this->user)->getJson('/auth/login-history');
        $response->assertOk();
    }
}
