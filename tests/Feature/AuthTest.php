<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Laravel\Passport\Passport;
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

    public function testKillUserSession(): void
    {
        $user = User::factory()->create();

//        $tokenResult = $user->createToken('Test Access Token');
//        \Log::info(print_r($tokenResult->token->id, true));
//        $token = $user->tokens()->find($tokenResult->token->id);
//        $token->delete();
//        \Log::info(print_r($user->tokens()->get()->toArray(), true));
    //    \Log::info(print_r($tokenResult->toArray(), true));
    //    \Log::info(print_r($tokenResult->token->user()->get()->toArray(), true));

//        $response = $this->postJson('/login', [
//            'email' => $user->email,
//            'password' => $user->password,
//        ]); //->assertOk();
//
//        \Log::info(print_r($response->getData(), true));
        Passport::actingAs($user);
        $response = $this->postJson('/auth/kill-session')
            ->assertUnauthorized();
        \Log::debug(print_r($response->getData()->data, true));

        //$tokenResult->token->revoke();
        //$tokenResult->token->delete();

//        $responseSessions = $this->getJson('/auth/login-history');
//        \Log::debug(print_r($responseSessions->getData()->data, true));
//        $responseSessions->assertOk();
    }

    public function testKillAllOldUserSessions(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);
        $this->postJson('/auth/kill-old-sessions')->assertNoContent();
    }
}
