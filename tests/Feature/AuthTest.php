<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response as HttpRespone;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use WithFaker;

    public function testLogin(): void
    {
        $response = $this->postJson('/user/login', [
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

        $response = $this->postJson('/user/reset-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(HttpRespone::HTTP_NO_CONTENT);
    }

    public function testSaveResetPassword(): void
    {
        $email = $this->faker->unique()->safeEmail;
        $password = 'Passwd###111';
        $newPassword = 'NewPasswd###111';

        $user = User::factory()->create([
            'name' => $this->faker->firstName() . ' '  . $this->faker->lastName(),
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $token = Password::createToken($user);
        $this->assertTrue(Password::tokenExists($user, $token));

        $this->patchJson('/user/save-reset-password', [
            'email' => $email,
            'password' => $password,
            'password_new' => $newPassword,
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
}
