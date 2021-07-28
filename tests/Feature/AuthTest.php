<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Request;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use WithFaker;

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

    public function testResetPassword(): void
    {
        $email = $this->faker->unique()->safeEmail;
        $password = 'Passwd###111';

        $user = User::factory()->create([
            'name' => $this->faker->firstName() . ' '  . $this->faker->lastName(),
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        Mail::fake();
        Mail::assertNothingSent();

        $response = $this->postJson('/users/reset-password', [
            'email' => $user->email,
        ]);

        $response->assertNoContent();
    }

    public function testSaveResetPassword(): void
    {
        $email = $this->faker->unique()->safeEmail;
        $newPassword = 'NewPasswd###111';

        $user = User::factory()->create([
            'name' => $this->faker->firstName() . ' '  . $this->faker->lastName(),
            'email' => $email,
        ]);

        $token = Password::createToken($user);
        $this->assertTrue(Password::tokenExists($user, $token));

        $this->patchJson('/users/save-reset-password', [
            'email' => $email,
            'password' => $newPassword,
            'token' => $token,
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Password::tokenExists($user, $token));
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

    public function testKillActiveSession(): void
    {
        $user = User::factory()->create();
        $token1 = $user->createToken('Test A Access Token');
        $token2 = $user->createToken('Test B Access Token');
        $token3 = $user->createToken('Test C Access Token');

        $token3->token->update([
           'ip' => Request::ip(),
         ]);

        $headers = ['Authorization' => 'Bearer ' . $token1->accessToken];

        // call the same session
        $this->getJson('/auth/kill-session/id:' . $token1->token->id, $headers)
            ->assertForbidden();

        // revoke selected sessions
        $this->getJson('/auth/kill-session/id:' . $token3->token->id, $headers)
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(
                [
                    'id' => $token3->token->id,
                    'token_status' => false,
                    'revoked' => true,
                ],
            );

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $token1->token->id,
            'user_id' => $user->getKey(),
            'revoked' => false,
        ]);
        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $token2->token->id,
            'user_id' => $user->getKey(),
            'revoked' => false,
        ]);
        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $token3->token->id,
            'user_id' => $user->getKey(),
            'revoked' => true,
        ]);
    }

    public function testKillAllSessions(): void
    {
        $user = User::factory()->create();

        $user->createToken('Test 1 Access Token');
        $user->createToken('Test 2 Access Token');
        $token = $user->createToken('Test 3 Access Token');

        $token->token->update([
          'ip' => Request::ip(),
        ]);

        $headers = ['Authorization' => 'Bearer ' . $token->accessToken];
        $this->getJson('/auth/kill-all-sessions', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(
                [
                    'ip' => Request::ip(),
                    'revoked' => false,
                ],
            );

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $token->token->id,
            'user_id' => $user->getKey(),
            'revoked' => false,
        ]);
    }
}
