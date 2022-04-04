<?php

namespace Tests\Feature;

use App\Enums\RoleType;
use App\Enums\TFAType;
use App\Enums\TokenType;
use App\Models\App;
use App\Models\OneTimeSecurityCode;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\TFAInitialization;
use App\Notifications\TFARecoveryCodes;
use App\Notifications\TFASecurityCode;
use App\Notifications\UserRegistered;
use App\Services\Contracts\OneTimeSecurityCodeContract;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use PHPGangsta_GoogleAuthenticator;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use WithFaker;

    private string $expectedLog;
    private OneTimeSecurityCodeContract $oneTimeSecurityCodeService;
    private array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->expectedLog = 'AuthException(code: 0): Invalid credentials at';
        $this->oneTimeSecurityCodeService = \Illuminate\Support\Facades\App::make(OneTimeSecurityCodeContract::class);

        $this->expected = [
            'token',
            'identity_token',
            'refresh_token',
            'user' => [
                'id',
                'email',
                'name',
                'avatar',
            ],
        ];
    }

    public function testLoginUnauthorized(): void
    {
        $response = $this->postJson('/login', [
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
            ->assertJsonStructure(['data' => $this->expected]);
    }

    public function testLoginInvalidCredential(): void
    {
        $this->user->givePermissionTo('auth.login');

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, $this->expectedLog);
            });

        $response = $this->actingAs($this->user)->postJson('/login', [
            'email' => $this->user->email,
            'password' => 'bad-password',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testLoginDisabledTfaCode(): void
    {
        $this->user->givePermissionTo('auth.login');

        $response = $this->actingAs($this->user)->postJson('/login', [
            'email' => $this->user->email,
            'password' => $this->password,
            'code' => 'code',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'Two-Factor Authentication is not setup.',
            ]);
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testLoginEnabledTfaNoCode($method, $secret): void
    {
        $this->user->givePermissionTo('auth.login');

        Notification::fake();

        $this->user->update([
            'tfa_type' => $method,
            'tfa_secret' => $secret,
            'is_tfa_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson('/login', [
            'email' => $this->user->email,
            'password' => $this->password,
        ]);

        if ($method === TFAType::EMAIL) {
            Notification::assertSentTo([$this->user], TFASecurityCode::class);
        }

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonFragment([
                'message' => 'Two-Factor Authentication is required',
            ]);
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testLoginEnabledTfaCode($method, $secret): void
    {
        $this->user->givePermissionTo('auth.login');

        Notification::fake();

        $this->user->update([
            'tfa_type' => $method,
            'tfa_secret' => $secret,
            'is_tfa_active' => true,
        ]);
        $code = '';

        if ($method === TFAType::EMAIL) {
            $code = $this->oneTimeSecurityCodeService->generateOneTimeSecurityCode(
                $this->user,
                Config::get('tfa.code_expires_time'),
            );
        } elseif ($method === TFAType::APP) {
            $google_authenticator = new PHPGangsta_GoogleAuthenticator();
            $code = $google_authenticator->getCode($secret);
        }

        $response = $this->actingAs($this->user)->postJson('/login', [
            'email' => $this->user->email,
            'password' => $this->password,
            'code' => $code,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => $this->expected]);

        $this->assertDatabaseCount('one_time_security_codes', 0);
    }

    public function testLoginEnabledTfaOldCode(): void
    {
        $this->user->givePermissionTo('auth.login');

        Notification::fake();

        $this->user->update([
            'tfa_type' => 'email',
            'tfa_secret' => null,
            'is_tfa_active' => true,
        ]);

        $code = $this->oneTimeSecurityCodeService->generateOneTimeSecurityCode(
            $this->user,
            Config::get('tfa.code_expires_time'),
        );

        // Wygenerowanie nowego kodu
        $this->actingAs($this->user)->postJson('/login', [
            'email' => $this->user->email,
            'password' => $this->password,
        ]);

        $response = $this->actingAs($this->user)->postJson('/login', [
            'email' => $this->user->email,
            'password' => $this->password,
            'code' => $code,
        ]);

        $response
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'Invalid Two-Factor Authentication token.',
            ]);

        $this->assertDatabaseCount('one_time_security_codes', 1);
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testLoginEnabledTfaRecoveryCode($method, $secret): void
    {
        $this->user->givePermissionTo('auth.login');

        Notification::fake();

        $this->user->update([
            'tfa_type' => $method,
            'tfa_secret' => $secret,
            'is_tfa_active' => true,
        ]);

        $code = $this->oneTimeSecurityCodeService->generateOneTimeSecurityCode($this->user);

        $response = $this->actingAs($this->user)->postJson('/login', [
            'email' => $this->user->email,
            'password' => $this->password,
            'code' => $code,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => $this->expected]);

        $this->assertDatabaseCount('one_time_security_codes', 0);
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testLoginEnabledTfaInvalidCode($method, $secret): void
    {
        $this->user->givePermissionTo('auth.login');

        Notification::fake();

        $this->user->update([
            'tfa_type' => $method,
            'tfa_secret' => $secret,
            'is_tfa_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson('/login', [
            'email' => $this->user->email,
            'password' => $this->password,
            'code' => 'INVALID',
        ]);

        $response
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'message' => 'Invalid Two-Factor Authentication token.',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testRefreshTokenUnauthorized($user): void
    {
        $token = $this->tokenService->createToken(
            $this->$user,
            new TokenType(TokenType::REFRESH),
        );

        $response = $this->actingAs($this->$user)->postJson('/auth/refresh', [
            'refresh_token' => $token,
        ]);

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testRefreshTokenMissing($user): void
    {
        $this->$user->givePermissionTo('auth.login');

        $response = $this->actingAs($this->$user)->postJson('/auth/refresh', [
            'refresh_token' => null,
        ]);

        $response->assertUnprocessable();
    }

    public function testRefreshTokenUser(): void
    {
        $this->user->givePermissionTo('auth.login');

        $token = $this->tokenService->createToken(
            $this->user,
            new TokenType(TokenType::REFRESH),
        );

        $response = $this->actingAs($this->user)->postJson('/auth/refresh', [
            'refresh_token' => $token,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'token',
                'identity_token',
                'refresh_token',
                'user' => [
                    'id',
                    'email',
                    'name',
                    'avatar',
                ],
            ],
            ]);
    }

    public function testRefreshTokenApp(): void
    {
        $this->application->givePermissionTo('auth.login');

        $token = $this->tokenService->createToken(
            $this->application,
            new TokenType(TokenType::REFRESH),
        );

        $response = $this->actingAs($this->application)->postJson('/auth/refresh', [
            'refresh_token' => $token,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'token',
                'identity_token',
                'refresh_token',
                'user' => [
                    'id',
                    'url',
                    'microfrontend_url',
                    'name',
                    'slug',
                    'version',
                    'description',
                    'icon',
                    'author',
                    'permissions',
                ],
            ],
            ])->assertJsonFragment([
                'identity_token' => null,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testRefreshTokenInvalidated($user): void
    {
        $this->$user->givePermissionTo('auth.login');

        $token = $this->tokenService->createToken(
            $this->$user,
            new TokenType(TokenType::REFRESH),
        );
        $this->tokenService->invalidateToken($token);

        $response = $this->actingAs($this->$user)->postJson('/auth/refresh', [
            'refresh_token' => $token,
        ]);

        $response->assertStatus(422);
    }

    public function testLogout(): void
    {
        $uuid = Str::uuid()->toString();
        $token = $this->tokenService->createToken(
            $this->user,
            new TokenType(TokenType::ACCESS),
            $uuid,
        );

        $this->withHeaders(
            $this->defaultHeaders + ['Authorization' => 'Bearer ' . $token],
        );

        $response = $this
            ->withHeaders($this->defaultHeaders + ['Authorization' => 'Bearer ' . $token])
            ->postJson('/auth/logout');
        $response->assertNoContent();

        $this->assertDatabaseHas('tokens', [
            'id' => $uuid,
            'invalidated' => true,
        ]);
    }

    public function testLogoutWithInvalidatedTokenAfterRefreshToken(): void
    {
        $this->user->givePermissionTo('auth.login');

        $response = $this->actingAs($this->user)->postJson('/login', [
            'email' => $this->user->email,
            'password' => $this->password,
        ]);

        $token = $response->getData()->data->token;
        $refreshToken = $response->getData()->data->refresh_token;

        $this
            ->json(
                'POST',
                '/auth/refresh',
                [
                    'refresh_token' => $refreshToken,
                ],
                $this->defaultHeaders + ['Authorization' => 'Bearer ' . $token]
            );

        $this
            ->json(
                'POST',
                '/auth/logout',
                [],
                $this->defaultHeaders + ['Authorization' => 'Bearer ' . $token]
            )
            ->assertStatus(422);
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

        $response = $this->postJson('/users/reset-password', [
            'email' => $user->email,
        ]);

        $response->assertForbidden();
    }

    public function testResetPassword(): void
    {
        $this->user->givePermissionTo('auth.password_reset');

        $email = $this->faker->unique()->safeEmail;
        $password = 'Passwd###111';

        $user = User::factory()->create([
            'name' => $this->faker->firstName() . ' '  . $this->faker->lastName(),
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        Mail::fake();
        Mail::assertNothingSent();

        $response = $this->actingAs($this->user)->postJson('/users/reset-password', [
            'email' => $user->email,
        ]);

        $response->assertNoContent();
    }

    public function testResetPasswordDifferentEmail(): void
    {
        $this->user->givePermissionTo('auth.password_reset');

        $email = $this->faker->unique()->safeEmail;
        $password = 'Passwd###111';

        User::factory()->create([
            'name' => $this->faker->firstName() . ' '  . $this->faker->lastName(),
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        Mail::fake();
        Mail::assertNothingSent();

        $response = $this->actingAs($this->user)->postJson('/users/reset-password', [
            'email' => $this->faker->unique()->safeEmail,
        ]);

        Mail::assertNothingSent();

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

        $response = $this->patchJson('/users/save-reset-password', [
            'email' => $email,
            'password' => $newPassword,
            'token' => $token,
        ]);

        $response->assertForbidden();
    }

    public function testSaveResetPassword(): void
    {
        $this->user->givePermissionTo('auth.password_reset');

        $email = $this->faker->unique()->safeEmail;
        $newPassword = 'NewPasswd###111';

        $user = User::factory()->create([
            'name' => $this->faker->firstName() . ' '  . $this->faker->lastName(),
            'email' => $email,
        ]);

        $token = Password::createToken($user);
        $this->assertTrue(Password::tokenExists($user, $token));

        $this->actingAs($this->user)->patchJson('/users/save-reset-password', [
            'email' => $email,
            'password' => $newPassword,
            'token' => $token,
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Password::tokenExists($user, $token));
    }

    public function testSaveResetPasswordInvalidToken(): void
    {
        $this->user->givePermissionTo('auth.password_reset');

        $email = $this->faker->unique()->safeEmail;
        $newPassword = 'NewPasswd###111';

        $user = User::factory()->create([
            'name' => $this->faker->firstName() . ' '  . $this->faker->lastName(),
            'email' => $email,
        ]);

        $token = Password::createToken($user);
        $this->assertTrue(Password::tokenExists($user, $token));

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains(
                    $message,
                    'AuthException(code: 0): The token is invalid or inactive. Try to reset your password again. at',
                );
            });

        $response = $this->actingAs($this->user)->json(
            'PATCH',
            '/users/save-reset-password',
            [
                'email' => $email,
                'password' => $newPassword,
                'token' => 'token',
            ],
        );

        $user->refresh();
        $response->assertStatus(422);
    }

    public function testChangePasswordUnauthorized(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('test'),
        ]);

        $response = $this->actingAs($user)->patchJson('/users/password', [
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

        $response = $this->actingAs($user)->patchJson('/users/password', [
            'password' => 'test',
            'password_new' => 'Test1@345678',
        ]);

        $response->assertNoContent();

        $user->refresh();
        $this->assertTrue(Hash::check('Test1@345678', $user->password));
    }

    public function testChangePasswordInvalidPassword(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('test'),
        ]);

        $user->givePermissionTo('auth.password_change');

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains($message, $this->expectedLog);
            });

        $response = $this->actingAs($user)->json('PATCH', '/users/password', [
            'password' => 'tests',
            'password_new' => 'Test1@345678',
        ]);

        $response->assertStatus(422);
    }

//    public function testLoginHistoryUnauthorized(): void
//    {
//        $response = $this->actingAs($this->user)->getJson('/auth/login-history');
//        $response->assertForbidden();
//    }
//
//    public function testLoginHistory(): void
//    {
//        $this->user->givePermissionTo('auth.sessions.show');
//
//        $response = $this->actingAs($this->user)->getJson('/auth/login-history');
//        $response->assertOk();
//    }
//
//    public function testKillActiveSessionUnauthorized(): void
//    {
//        $user = User::factory()->create();
//        $token = $user->createToken('Test Active Token');
//        $headers = ['Authorization' => 'Bearer ' . $token->accessToken];
//
//        $this->getJson('/auth/kill-session/id:' . $token->token->id, $headers)
//            ->assertForbidden();
//    }
//
//    public function testKillActiveSession(): void
//    {
//        $user = User::factory()->create();
//        $user->givePermissionTo('auth.sessions.revoke');
//
//        $token = $user->createToken('Test Active Token');
//        $headers = ['Authorization' => 'Bearer ' . $token->accessToken];
//
//        $this->getJson('/auth/kill-session/id:' . $token->token->id, $headers)
//            ->assertStatus(422);
//
//        $this->assertDatabaseHas('oauth_access_tokens', [
//            'id' => $token->token->id,
//            'user_id' => $user->getKey(),
//            'revoked' => false,
//        ]);
//    }
//
//    public function testKillOldSessionUnauthorized(): void
//    {
//        $user = User::factory()->create();
//        $token1 = $user->createToken('Test A Access Token');
//        $token3 = $user->createToken('Test C Access Token');
//
//        $headers = ['Authorization' => 'Bearer ' . $token1->accessToken];
//        $this->getJson('/auth/kill-session/id:' . $token3->token->id, $headers)
//            ->assertForbidden();
//    }
//
//    public function testKillOldSession(): void
//    {
//        $user = User::factory()->create();
//        $user->givePermissionTo('auth.sessions.revoke');
//
//        $token1 = $user->createToken('Test A Access Token');
//        $token2 = $user->createToken('Test B Access Token');
//        $token3 = $user->createToken('Test C Access Token');
//
//        $headers = ['Authorization' => 'Bearer ' . $token1->accessToken];
//        $this->getJson('/auth/kill-session/id:' . $token3->token->id, $headers)
//            ->assertOk()
//            ->assertJsonCount(3, 'data')
//            ->assertJsonFragment(
//                [
//                    'id' => $token3->token->id,
//                    'current_session' => false,
//                    'revoked' => true,
//                ],
//            );
//
//        $this->assertDatabaseHas('oauth_access_tokens', [
//            'id' => $token1->token->id,
//            'user_id' => $user->getKey(),
//            'revoked' => false,
//        ]);
//        $this->assertDatabaseHas('oauth_access_tokens', [
//            'id' => $token2->token->id,
//            'user_id' => $user->getKey(),
//            'revoked' => false,
//        ]);
//        $this->assertDatabaseHas('oauth_access_tokens', [
//            'id' => $token3->token->id,
//            'user_id' => $user->getKey(),
//            'revoked' => true,
//        ]);
//    }
//
//    public function testKillAllSessionsUnauthorized(): void
//    {
//        $user = User::factory()->create();
//
//        $token3 = $user->createToken('Test 3 Access Token');
//
//        $token3->token->update([
//          'ip' => Request::ip(),
//        ]);
//
//        $headers = ['Authorization' => 'Bearer ' . $token3->accessToken];
//        $this->getJson('/auth/kill-all-sessions', $headers)
//            ->assertForbidden();
//    }
//
//    public function testKillAllSessions(): void
//    {
//        $user = User::factory()->create();
//        $user->givePermissionTo('auth.sessions.revoke');
//
//        $token1 = $user->createToken('Test 1 Access Token');
//        $token2 = $user->createToken('Test 2 Access Token');
//        $token3 = $user->createToken('Test 3 Access Token');
//
//        $token3->token->update([
//            'ip' => Request::ip(),
//        ]);
//
//        $headers = ['Authorization' => 'Bearer ' . $token3->accessToken];
//        $this->getJson('/auth/kill-all-sessions', $headers)
//            ->assertOk()
//            ->assertJsonCount(1, 'data')
//            ->assertJsonFragment(
//                [
//                    'current_session' => true,
//                    'ip' => Request::ip(),
//                    'revoked' => false,
//                ],
//            );
//
//        $this->assertDatabaseHas('oauth_access_tokens', [
//            'id' => $token3->token->id,
//            'user_id' => $user->getKey(),
//            'revoked' => false,
//        ]);
//
//        // check revoked sessions
//        $resultToken1 = $this->checkToken($token1->token->id, true);
//        $this->assertCount(1, $resultToken1);
//
//        $resultToken2 = $this->checkToken($token2->token->id, true);
//        $this->assertCount(1, $resultToken2);
//    }
//
//    private function checkToken(string $idToken, bool $isRevoke)
//    {
//        return Passport::token()
//            ->where('id', $idToken)
//            ->where('revoked', $isRevoke)
//            ->get();
//    }

    public function testProfileUnauthenticated(): void
    {
        $this->getJson('/auth/profile')
            ->assertOk()
            ->assertJsonFragment([
                'id' => null,
                'name' => 'Unauthenticated',
                'email' => null,
            ]);
    }

    public function testProfileUser(): void
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
                ],
                ],
                'permissions' => [
                    'permission.1',
                    'permission.2',
                ],
            ],
            ]);
    }

    public function testProfileApp(): void
    {
        $app = App::factory()->create();

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $app->syncPermissions([$permission1, $permission2]);

        $this->actingAs($app)->getJson('/auth/profile')
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $app->getKey(),
                'url' => $app->url,
                'microfrontend_url' => $app->microfrontend_url,
                'name' => $app->name,
                'slug' => $app->slug,
                'version' => $app->version,
                'description' => $app->description,
                'icon' => $app->icon,
                'author' => $app->author,
                'permissions' => [
                    'permission.1',
                    'permission.2',
                ],
            ],
            ]);
    }

    public function testCheckIdentityUnauthorized(): void
    {
        $user = User::factory()->create();

        $token = $this->tokenService->createToken(
            $user,
            new TokenType(TokenType::IDENTITY),
        );

        $this->actingAs($user)->getJson("/auth/check/${token}")
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCheckIdentityInvalidToken($user): void
    {
        $this->$user->givePermissionTo('auth.check_identity');

        $otherUser = User::factory()->create();

        $token = $this->tokenService->createToken(
            $otherUser,
            new TokenType(TokenType::IDENTITY),
        ) . 'invalid_hash';

        $this->actingAs($this->$user)->getJson("/auth/check/${token}")
            ->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCheckIdentityNoToken($user): void
    {
        $this->$user->givePermissionTo('auth.check_identity');

        $this->actingAs($this->$user)->getJson('/auth/check')
            ->assertOk()
            ->assertJsonFragment([
                'id' => null,
                'name' => 'Unauthenticated',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCheckIdentity($user): void
    {
        $this->$user->givePermissionTo('auth.check_identity');

        $otherUser = User::factory()->create();
        $role1 = Role::create(['name' => 'Role 1']);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);

        $role1->syncPermissions([$permission1, $permission2]);
        $otherUser->syncRoles([$role1]);

        $token = $this->tokenService->createToken(
            $otherUser,
            new TokenType(TokenType::IDENTITY),
        );

        $this->actingAs($this->$user)->getJson("/auth/check/${token}")
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $otherUser->getKey(),
                'name' => $otherUser->name,
                'avatar' => $otherUser->avatar,
                'permissions' => [
                    'permission.1',
                    'permission.2',
                ],
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCheckIdentityNoAppMapping($user): void
    {
        App::factory()->create([
            'slug' => 'app_slug',
        ]);

        $this->$user->givePermissionTo('auth.check_identity');

        $otherUser = User::factory()->create();
        $role1 = Role::create(['name' => 'Role 1']);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);
        $permission3 = Permission::create(['name' => 'app.app_slug.raw_name']);

        $role1->syncPermissions([$permission1, $permission2, $permission3]);
        $otherUser->syncRoles([$role1]);

        $token = $this->tokenService->createToken(
            $otherUser,
            new TokenType(TokenType::IDENTITY),
        );

        $this->actingAs($this->$user)->getJson("/auth/check/${token}")
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $otherUser->getKey(),
                'name' => $otherUser->name,
                'avatar' => $otherUser->avatar,
                'permissions' => [
                    'app.app_slug.raw_name',
                    'permission.1',
                    'permission.2',
                ],
            ],
            ]);
    }

    public function testCheckIdentityAppMapping(): void
    {
        $app = App::factory()->create([
            'slug' => 'app_slug',
        ]);

        $app->givePermissionTo('auth.check_identity');

        $user = User::factory()->create();
        $role1 = Role::create(['name' => 'Role 1']);

        $permission1 = Permission::create(['name' => 'permission.1']);
        $permission2 = Permission::create(['name' => 'permission.2']);
        $permission3 = Permission::create(['name' => 'app.app_slug.raw_name']);

        $role1->syncPermissions([$permission1, $permission2, $permission3]);
        $user->syncRoles([$role1]);

        $token = $this->tokenService->createToken(
            $user,
            new TokenType(TokenType::IDENTITY),
        );

        $this->actingAs($app)->getJson("/auth/check/${token}")
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'avatar' => $user->avatar,
                'permissions' => [
                    'permission.1',
                    'permission.2',
                    'raw_name',
                ],
            ],
            ]);
    }

    public function testSetupTfaUnauthorized(): void
    {
        $this->json('POST', '/auth/2fa/setup', [
            'type' => TFAType::APP,
        ])->assertForbidden();
    }

    public function testConfirmTfaUnauthorized(): void
    {
        $this->json('POST', '/auth/2fa/confirm', [
            'code' => '123456',
        ])->assertForbidden();
    }

    public function tfaMethodProvider(): array
    {
        return [
            'as app 2fa' => [TFAType::APP, 'secret'],
            'as email 2fa' => [TFAType::EMAIL, null],
        ];
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testAlreadySetupTfa($method, $secret): void
    {
        $this->user->update([
            'tfa_type' => $method,
            'tfa_secret' => $secret,
            'is_tfa_active' => true,
        ]);

        $this->actingAs($this->user)->json('POST', '/auth/2fa/setup', [
            'type' => $method,
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Two-Factor Authentication is already setup.']);
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function confirmAlreadySetupTfa($method, $secret): void
    {
        $this->user->update([
            'tfa_type' => $method,
            'tfa_secret' => $secret,
            'is_tfa_active' => true,
        ]);

        $this->actingAs($this->user)->json('POST', '/auth/2fa/confirm', [
            'code' => '123456',
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Two-Factor Authentication is already setup.']);
    }

    public function confirmNoSetupTfa(): void
    {
        $this->actingAs($this->user)->json('POST', '/auth/2fa/confirm', [
            'code' => '123456',
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'First select Two-Factor Authentication type.']);
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function confirmInvalidToken($method, $secret): void
    {
        $this->user->update([
            'tfa_type' => $method,
            'tfa_secret' => $secret,
            'is_tfa_active' => false,
        ]);

        $this->actingAs($this->user)->json('POST', '/auth/2fa/confirm', [
            'code' => 'INVALID',
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Invalid Two-Factor Authentication token.']);
    }

    public function confirmExpiredToken(): void
    {
        $this->user->update([
            'tfa_type' => TFAType::EMAIL,
            'is_tfa_active' => false,
        ]);

        $this->actingAs($this->user)->json('POST', '/auth/2fa/confirm', [
            'code' => $this->oneTimeSecurityCodeService->generateOneTimeSecurityCode($this->user, 1),
        ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Invalid Two-Factor Authentication token.']);
    }

    public function testSetupAppTfa(): void
    {
        $response = $this->actingAs($this->user)->json('POST', '/auth/2fa/setup', [
            'type' => TFAType::APP,
        ]);

        $secret = $response->getData()->data->secret;

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'tfa_type' => TFAType::APP,
            'tfa_secret' => $secret,
            'is_tfa_active' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'type',
                'secret',
                'qr_code_url',
            ],
            ]);
    }

    public function testConfirmAppTfa(): void
    {
        Notification::fake();

        $google_authenticator = new PHPGangsta_GoogleAuthenticator();

        $secret = $google_authenticator->createSecret();
        $code = $google_authenticator->getCode($secret);

        $this->user->update([
            'tfa_type' => TFAType::APP,
            'tfa_secret' => $secret,
        ]);

        $this->actingAs($this->user)->json('POST', '/auth/2fa/confirm', [
            'code' => $code,
        ])->assertOk()->assertJsonStructure(['data' => [
            'recovery_codes',
        ],
        ]);

        Notification::assertSentTo(
            [$this->user],
            TFARecoveryCodes::class
        );

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'is_tfa_active' => true,
        ]);

        $this->assertDatabaseCount('one_time_security_codes', 3);
    }

    public function testSetupEmailTfa(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->user)->json('POST', '/auth/2fa/setup', [
            'type' => TFAType::EMAIL,
        ]);

        Notification::assertSentTo(
            [$this->user],
            TFAInitialization::class
        );

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'tfa_type' => TFAType::EMAIL,
            'tfa_secret' => null,
            'is_tfa_active' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'type',
            ],
            ]);
    }

    public function testConfirmEmailTfa(): void
    {
        Notification::fake();

        $code = $this->oneTimeSecurityCodeService->generateOneTimeSecurityCode(
            $this->user,
            Config::get('tfa.code_expires_time'),
        );

        $this->user->update([
            'tfa_type' => TFAType::EMAIL,
        ]);

        $this->actingAs($this->user)->json('POST', '/auth/2fa/confirm', [
            'code' => $code,
        ])->assertOk()->assertJsonStructure(['data' => [
            'recovery_codes',
        ],
        ]);

        Notification::assertSentTo(
            [$this->user],
            TFARecoveryCodes::class
        );

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'is_tfa_active' => true,
        ]);

        $this->assertDatabaseCount('one_time_security_codes', 3);
    }

    public function testRecoveryCodesCreateUnauthorized(): void
    {
        $this->json('POST', '/auth/2fa/recovery/create', [
            'password' => $this->password,
        ])->assertForbidden();
    }

    public function testRecoveryCodesCreateInvalidPassword(): void
    {
        $this->actingAs($this->user)->json('POST', '/auth/2fa/recovery/create', [
            'password' => 'invalid',
        ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Invalid credentials',
            ]);
    }

    public function testRecoveryCodesCreateNoTfa(): void
    {
        $this->actingAs($this->user)->json('POST', '/auth/2fa/recovery/create', [
            'password' => $this->password,
        ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Two-Factor Authentication is not setup.',
            ]);
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testRecoveryCodesCreate($method, $secret): void
    {
        Notification::fake();

        $this->user->update([
            'tfa_type' => $method,
            'tfa_secret' => $secret,
            'is_tfa_active' => true,
        ]);

        $response = $this->actingAs($this->user)->json('POST', '/auth/2fa/recovery/create', [
            'password' => $this->password,
        ])->assertOk();

        Notification::assertSentTo(
            [$this->user],
            TFARecoveryCodes::class
        );

        $recovery_codes = OneTimeSecurityCode::where('user_id', '=', $this->user->getKey())
            ->whereNull('expires_at')
            ->get();

        $response->assertJsonStructure(['data' => [
            'recovery_codes',
        ],
        ]);

        $this->assertDatabaseCount('one_time_security_codes', count($recovery_codes));
    }

    public function testRemoveTfaUnauthorized(): void
    {
        $this->json('POST', '/auth/2fa/remove', [
            'password' => $this->password,
        ])->assertForbidden();
    }

    public function testRemoveTfaNoTfa(): void
    {
        $this->actingAs($this->user)->json('POST', '/auth/2fa/remove', [
            'password' => $this->password,
        ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Two-Factor Authentication is not setup.',
            ]);
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testRemoveTfa($method, $secret): void
    {
        $this->user->update([
            'tfa_type' => $method,
            'tfa_secret' => $secret,
            'is_tfa_active' => true,
        ]);

        OneTimeSecurityCode::factory([
            'user_id' => $this->user->getKey(),
        ])->create();

        $this->actingAs($this->user)->json('POST', '/auth/2fa/remove', [
            'password' => $this->password,
        ])->assertNoContent();

        $this->assertDatabaseCount('one_time_security_codes', 0);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'tfa_type' => null,
            'tfa_secret' => null,
            'is_tfa_active' => false,
        ]);
    }

    public function testRemoveUserTfaUnauthorized(): void
    {
        $otherUser = User::factory()->create();

        $this->actingAs($this->user)->json('POST', '/users/id:' . $otherUser->getKey() . '/2fa/remove')
            ->assertForbidden();
    }

    public function testRemoveUserTfaNoTfa(): void
    {
        $this->user->givePermissionTo('users.2fa_remove');

        $otherUser = User::factory()->create();

        $this->actingAs($this->user)->json('POST', '/users/id:' . $otherUser->getKey() . '/2fa/remove')
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Two-Factor Authentication is not setup.',
            ]);
    }

    public function testRemoveUserTfaYourself(): void
    {
        $this->user->givePermissionTo('users.2fa_remove');

        $this->actingAs($this->user)->json('POST', '/users/id:' . $this->user->getKey() . '/2fa/remove')
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'You cannot remove 2FA yourself in this way.',
            ]);
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testRemoveUserTfa($method, $secret): void
    {
        $this->user->givePermissionTo('users.2fa_remove');

        $otherUser = User::factory([
            'tfa_type' => $method,
            'tfa_secret' => $secret,
            'is_tfa_active' => true,
        ])->create();

        OneTimeSecurityCode::factory([
            'user_id' => $otherUser->getKey(),
        ])->create();

        $this->actingAs($this->user)->json('POST', '/users/id:' . $otherUser->getKey() . '/2fa/remove')
            ->assertNoContent();

        $this->assertDatabaseCount('one_time_security_codes', 0);
        $this->assertDatabaseHas('users', [
            'id' => $otherUser->getKey(),
            'tfa_type' => null,
            'tfa_secret' => null,
            'is_tfa_active' => false,
        ]);
    }

    public function testRegisterUnauthorized(): void
    {
        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $this->faker->email(),
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
        ])->assertForbidden();
    }

    public function testRegisterEmailTaken(): void
    {
        Notification::fake();

        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $this->user->email,
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Notification::assertNothingSent();
    }

    public function testRegister(): void
    {
        Notification::fake();

        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $email = $this->faker->email();
        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $email,
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'avatar',
                    'roles',
                ],
            ])
            ->assertJsonFragment([
                'name' => 'Registered user',
                'email' => $email,
            ])
            ->assertJsonFragment([
                'name' => 'Authenticated',
                'assignable' => false,
                'deletable' => false,
            ]);

        $user = User::where('email', $email)->first();

        Notification::assertSentTo(
            [$user],
            UserRegistered::class,
        );
    }

//    public function testAuthWithReokedToken(): void
//    {
//        $user = User::factory()->create();
//        $token = $user->createToken('Test Access Token');
//        $token->token->revoke();
//
//        $headers = ['Authorization' => 'Bearer ' . $token->accessToken];
//        $this->getJson('/auth/kill-all-sessions', $headers)
//            ->assertUnauthorized();
//    }
}
