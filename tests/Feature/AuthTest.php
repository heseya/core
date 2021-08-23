<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Request;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use WithFaker;

    public function testLoginUnauthorized(): void
    {
        $response = $this->actingAs($this->user)->postJson('/login', [
            'email' => $this->user->email,
            'password' => $this->password,
        ]);

        $response->assertForbidden();
    }

    public function testLogin(): void
    {
        $this->user->givePermissionTo('auth.login');

        $response = $this->actingAs($this->user)->postJson('/login', [
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

    public function testLoginInvalidCredential(): void
    {
        $response = $this->postJson('/login', [
            'email' => $this->user->email,
            'password' => 'bad-password',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testLogout(): void
    {
        $response = $this->actingAs($this->user)->actingAs($this->user)
            ->postJson('/auth/logout');
        $response->assertNoContent();
    }

    public function testResetPasswordUnauthorized(): void
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

        $response = $this->actingAs($user)->postJson('/users/reset-password', [
            'email' => $user->email,
        ]);

        $response->assertForbidden();
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
        $user->givePermissionTo('auth.password_reset');

        Mail::fake();
        Mail::assertNothingSent();

        $response = $this->actingAs($user)->postJson('/users/reset-password', [
            'email' => $user->email,
        ]);

        $response->assertNoContent();
    }

    public function testSaveResetPasswordUnauthorized(): void
    {
        $email = $this->faker->unique()->safeEmail;
        $newPassword = 'NewPasswd###111';

        $user = User::factory()->create([
            'name' => $this->faker->firstName() . ' '  . $this->faker->lastName(),
            'email' => $email,
        ]);

        $token = Password::createToken($user);

        $response = $this->actingAs($this->user)->patchJson('/users/save-reset-password', [
            'email' => $email,
            'password' => $newPassword,
            'token' => $token,
        ]);

        $response->assertForbidden();
    }

    function testSaveResetPassword(): void
    {
        $email = $this->faker->unique()->safeEmail;
        $newPassword = 'NewPasswd###111';

        $user = User::factory()->create([
            'name' => $this->faker->firstName() . ' '  . $this->faker->lastName(),
            'email' => $email,
        ]);

        $user->givePermissionTo('auth.password_reset');

        $token = Password::createToken($user);
        $this->assertTrue(Password::tokenExists($user, $token));

        $this->actingAs($user)->patchJson('/users/save-reset-password', [
            'email' => $email,
            'password' => $newPassword,
            'token' => $token,
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Password::tokenExists($user, $token));
    }

    public function testChangePasswordUnauthorized(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('test'),
        ]);

        $response = $this->actingAs($user)->patchJson('/user/password', [
            'password' => 'test',
            'password_new' => 'Test1@3456',
        ]);

        $response->assertForbidden();
    }

    public function testChangePassword(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('test'),
        ]);

        $user->givePermissionTo('auth.password_change');

        $response = $this->actingAs($user)->patchJson('/user/password', [
            'password' => 'test',
            'password_new' => 'Test1@3456',
        ]);

        $response->assertNoContent();

        $user->refresh();
        $this->assertTrue(Hash::check('Test1@3456', $user->password));
    }

    public function testLoginHistoryUnauthorized(): void
    {
        $response = $this->actingAs($this->user)->getJson('/auth/login-history');
        $response->assertForbidden();
    }

    public function testLoginHistory(): void
    {
        $this->user->givePermissionTo('auth.sessions.show');

        $response = $this->actingAs($this->user)->getJson('/auth/login-history');
        $response->assertOk();
    }

    public function testKillActiveSessionUnauthorized(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Test Active Token');
        $headers = ['Authorization' => 'Bearer ' . $token->accessToken];

        $this->getJson('/auth/kill-session/id:' . $token->token->id, $headers)
            ->assertForbidden();
    }

    public function testKillActiveSession(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('auth.sessions.revoke');

        $token = $user->createToken('Test Active Token');
        $headers = ['Authorization' => 'Bearer ' . $token->accessToken];

        $this->getJson('/auth/kill-session/id:' . $token->token->id, $headers)
            ->assertStatus(422);

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $token->token->id,
            'user_id' => $user->getKey(),
            'revoked' => false,
        ]);
    }

    public function testKillOldSessionUnauthorized(): void
    {
        $user = User::factory()->create();
        $token1 = $user->createToken('Test A Access Token');
        $token3 = $user->createToken('Test C Access Token');

        $headers = ['Authorization' => 'Bearer ' . $token1->accessToken];
        $this->getJson('/auth/kill-session/id:' . $token3->token->id, $headers)
            ->assertForbidden();
    }

    public function testKillOldSession(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('auth.sessions.revoke');

        $token1 = $user->createToken('Test A Access Token');
        $token2 = $user->createToken('Test B Access Token');
        $token3 = $user->createToken('Test C Access Token');

        $headers = ['Authorization' => 'Bearer ' . $token1->accessToken];
        $this->getJson('/auth/kill-session/id:' . $token3->token->id, $headers)
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(
                [
                    'id' => $token3->token->id,
                    'current_session' => false,
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

    public function testKillAllSessionsUnauthorized(): void
    {
        $user = User::factory()->create();

        $token3 = $user->createToken('Test 3 Access Token');

        $token3->token->update([
          'ip' => Request::ip(),
        ]);

        $headers = ['Authorization' => 'Bearer ' . $token3->accessToken];
        $this->getJson('/auth/kill-all-sessions', $headers)
            ->assertForbidden();
    }

    public function testKillAllSessions(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('auth.sessions.revoke');

        $token1 = $user->createToken('Test 1 Access Token');
        $token2 = $user->createToken('Test 2 Access Token');
        $token3 = $user->createToken('Test 3 Access Token');

        $token3->token->update([
            'ip' => Request::ip(),
        ]);

        $headers = ['Authorization' => 'Bearer ' . $token3->accessToken];
        $this->getJson('/auth/kill-all-sessions', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(
                [
                    'current_session' => true,
                    'ip' => Request::ip(),
                    'revoked' => false,
                ],
            );

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $token3->token->id,
            'user_id' => $user->getKey(),
            'revoked' => false,
        ]);

        // check revoked sessions
        $resultToken1 = $this->checkToken($token1->token->id, true);
        $this->assertCount(1, $resultToken1);

        $resultToken2 = $this->checkToken($token2->token->id, true);
        $this->assertCount(1, $resultToken2);
    }

    private function checkToken(string $idToken, bool $isRevoke)
    {
        return Passport::token()
            ->where('id', $idToken)
            ->where('revoked', $isRevoke)
            ->get();
    }

    public function testProfile(): void
    {
        $user = User::factory()->create();
        $role1 = Role::create(['name' => 'Role 1']);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $role1->syncPermissions([$permission1, $permission2]);
        $user->syncRoles([$role1]);

        $this->actingAs($user)->getJson('/auth/profile')
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $user->getKey(),
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'roles' => [[
                    $role1->getKeyName() => $role1->getKey(),
                    'name' => $role1->name,
                    'description' => $role1->description,
                    'assignable' => true,
                ]],
                'permissions' => [
                    'permission.1',
                    'permission.2',
                ],
            ]]);
    }
}
