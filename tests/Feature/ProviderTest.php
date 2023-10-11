<?php

namespace Tests\Feature;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Enums\ValidationError;
use App\Models\AuthProvider as AuthProviderModel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ProviderTest extends TestCase
{
    public static function socialMediaProvider(): array
    {
        return [
            'google' => ['google'],
            'facebook' => ['facebook'],
            'apple' => ['apple'],
            'github' => ['github'],
            'gitlab' => ['gitlab'],
            'bitbucket' => ['bitbucket'],
            'linkedin' => ['linkedin'],
        ];
    }

    public function testProvidersList(): void
    {
        AuthProviderModel::query()
            ->where('key', 'facebook')
            ->update(['active' => true]);

        $this
            ->json('GET', '/auth/providers')
            ->assertJson([
                'data' => [
                    0 => [
                        'key' => 'facebook',
                        'active' => true,
                    ],
                    1 => [
                        'key' => 'google',
                        'active' => false,
                    ],
                    2 => [
                        'key' => 'apple',
                        'active' => false,
                    ],
                    3 => [
                        'key' => 'github',
                        'active' => false,
                    ],
                    4 => [
                        'key' => 'gitlab',
                        'active' => false,
                    ],
                    5 => [
                        'key' => 'bitbucket',
                        'active' => false,
                    ],
                    6 => [
                        'key' => 'linkedin',
                        'active' => false,
                    ],
                ],
            ]);
    }

    public function testProvidersListActive(): void
    {
        AuthProviderModel::query()
            ->where('key', 'facebook')
            ->update(['active' => true]);

        $this
            ->json('GET', '/auth/providers', ['active' => true])
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['key' => 'facebook']);
    }

    public function testProvidersListInactive(): void
    {
        AuthProviderModel::query()
            ->whereNot('key', 'facebook')
            ->update(['active' => true]);

        $this
            ->json('GET', '/auth/providers', ['active' => false])
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['key' => 'facebook']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testGetProvider($user): void
    {
        $this->{$user}->givePermissionTo('auth.providers.manage');
        $this
            ->actingAs($this->{$user})
            ->json('GET', '/auth/providers/facebook')
            ->assertJsonFragment([
                'key' => 'facebook',
                'active' => false,
                'client_id' => null,
                'client_secret' => null,
            ]);
    }

    public function testGetProviderWithoutPermission(): void
    {
        $this
            ->json('GET', '/auth/providers/facebook')
            ->assertJsonFragment([
                'key' => 'facebook',
                'active' => false,
            ])
            ->assertJsonMissing([
                'client_id' => null,
                'client_secret' => null,
            ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $this
            ->json('PATCH', '/auth/providers/facebook', [
                'active' => true,
                'client_id' => 'test_id',
                'client_secret' => 'test_secret',
            ])
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->{$user}->givePermissionTo('auth.providers.manage');

        $response = $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/auth/providers/facebook', [
                'active' => true,
                'client_id' => 'test_id',
                'client_secret' => 'test_secret',
            ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'active' => true,
                'client_id' => 'test_id',
                'client_secret' => 'test_secret',
            ]);

        $this->assertDatabaseHas('auth_providers', [
            'id' => $response->getData()->id,
            'active' => true,
            'client_id' => 'test_id',
            'client_secret' => 'test_secret',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateActiveWithoutConfig($user): void
    {
        $this->{$user}->givePermissionTo('auth.providers.manage');

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/auth/providers/facebook', [
                'active' => true,
            ])
            ->assertJsonFragment(['key' => ValidationError::AUTHPROVIDERACTIVE]);
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testRedirect($key): void
    {
        AuthProviderModel::query()
            ->where('key', $key)
            ->update([
                'active' => true,
                'client_secret' => '***REMOVED***',
                'client_id' => '***REMOVED***',
            ]);

        $this
            ->json('POST', "/auth/providers/{$key}/redirect", [
                'return_url' => 'http://localhost',
            ])
            ->assertOk();
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testRedirectInactive($key): void
    {
        $response = $this->json('post', "auth/providers/{$key}/redirect", [
            'return_url' => 'http://localhost',
        ]);

        $response->assertJsonFragment([
            'key' => Exceptions::CLIENT_PROVIDER_IS_NOT_ACTIVE->name,
        ]);
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testRedirectNoConfig($key): void
    {
        $response = $this->json('post', "auth/providers/{$key}/redirect", [
            'return_url' => 'http://localhost',
        ]);

        $response->assertJsonFragment([
            'key' => Exceptions::CLIENT_PROVIDER_HAS_NO_CONFIG->name,
        ]);
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testLoginUnauthorized($key): void
    {
        $this->json(
            'POST',
            "auth/providers/{$key}/login",
            [
                'return_url' => 'https://example.com?code=test',
            ],
        )->assertForbidden();
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testLoginNewUser($key): void
    {
        $this->user->givePermissionTo(['auth.register']);
        $this->mockSocialiteUser($key);

        AuthProviderModel::query()
            ->where('key', $key)
            ->update([
                'active' => true,
                'client_id' => 'test_id',
                'client_secret' => 'test_secret',
            ]);

        $response = $this->actingAs($this->user)->json(
            'POST',
            "auth/providers/{$key}/login",
            [
                'return_url' => 'https://example.com?code=test',
            ],
        );

        $response->assertJsonStructure(
            [
                'data' => [
                    'token',
                    'identity_token',
                    'refresh_token',
                    'user' => [
                        'id',
                        'email',
                        'name',
                        'avatar',
                        'roles',
                        'shipping_addresses',
                        'billing_addresses',
                        'permissions',
                    ],
                ],
            ],
        );
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testLoginExistingUser($key): void
    {
        $this->user->givePermissionTo(['auth.register']);
        $this->mockSocialiteUser($key);

        AuthProviderModel::query()
            ->where('key', $key)
            ->update([
                'active' => true,
                'client_id' => 'test_id',
                'client_secret' => 'test_secret',
            ]);

        $existingUser = User::factory()->create([
            'name' => 'test user',
            'email' => 'test.user@gmail.com',
            'password' => null,
        ]);

        $existingUser->providers()->create([
            'provider' => $key,
            'provider_user_id' => 123456789,
            'user_id' => $existingUser->getKey(),
        ]);

        $response = $this->actingAs($this->user)->json(
            'POST',
            "auth/providers/{$key}/login",
            [
                'return_url' => 'https://example.com?code=test',
            ],
        );

        $response->assertJsonStructure(
            [
                'data' => [
                    'token',
                    'identity_token',
                    'refresh_token',
                    'user' => [
                        'id',
                        'email',
                        'name',
                        'avatar',
                        'roles',
                        'shipping_addresses',
                        'billing_addresses',
                        'permissions',
                    ],
                ],
            ],
        );
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testLoginExistingStandardUserRegistered($key): void
    {
        $this->user->givePermissionTo(['auth.register']);
        $this->mockSocialiteUser($key);

        AuthProviderModel::query()
            ->where('key', $key)
            ->update([
                'active' => true,
                'client_id' => 'test_id',
                'client_secret' => 'test_secret',
            ]);

        $existingUser = User::factory()->create([
            'name' => 'test user',
            'email' => 'test.user@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($this->user)->json(
            'POST',
            "auth/providers/{$key}/login",
            [
                'return_url' => 'https://example.com?code=test',
            ],
        )
            ->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment(['message' => Exceptions::CLIENT_ALREADY_HAS_ACCOUNT]);

        $mergeToken = $response->json('error.errors.merge_token');

        $this->assertDatabaseHas('user_providers', [
            'provider' => $key,
            'user_id' => $existingUser->getKey(),
            'merge_token' => $mergeToken,
        ]);
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testLoginExistingStandardUserRegisteredAndProviderAlreadyUsed($key): void
    {
        $this->user->givePermissionTo(['auth.register']);

        $this->mockSocialiteUser($key);

        AuthProviderModel::query()
            ->where('key', $key)
            ->update([
                'active' => true,
                'client_id' => 'test_id',
                'client_secret' => 'test_secret',
            ]);

        $existingUser = User::factory()->create([
            'name' => 'test user',
            'email' => 'test.user@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $oldMergeToken = Str::random(128);
        $existingUser->providers()->create([
            'provider' => $key,
            'provider_user_id' => 123456789,
            'user_id' => $existingUser->getKey(),
            'merge_token' => $oldMergeToken,
            'merge_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $response = $this->actingAs($this->user)->json(
            'POST',
            "auth/providers/{$key}/login",
            [
                'return_url' => 'https://example.com?code=test',
            ],
        )
            ->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment(['message' => Exceptions::CLIENT_ALREADY_HAS_ACCOUNT]);

        $mergeToken = $response->json('error.errors.merge_token');

        $this->assertDatabaseCount('user_providers', 1);

        $this->assertDatabaseHas('user_providers', [
            'provider' => $key,
            'user_id' => $existingUser->getKey(),
            'merge_token' => $mergeToken,
        ]);

        $this->assertDatabaseMissing('user_providers', [
            'provider' => $key,
            'user_id' => $existingUser->getKey(),
            'merge_token' => $oldMergeToken,
        ]);
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testMergeAccountUnauthorized($key): void
    {
        $this->mockSocialiteUser($key);

        AuthProviderModel::query()
            ->where('key', $key)
            ->update([
                'active' => true,
                'client_id' => 'test_id',
                'client_secret' => 'test_secret',
            ]);

        $existingUser = User::factory()->create([
            'name' => 'test user',
            'email' => 'test.user@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $mergeToken = Str::random(128);
        $existingUser->providers()->create([
            'provider' => $key,
            'provider_user_id' => 123456789,
            'user_id' => $existingUser->getKey(),
            'merge_token' => $mergeToken,
            'merge_token_expires_at' => Carbon::now()->subDay(),
        ]);

        $this
            ->json('POST', '/auth/providers/merge-account', [
                'merge_token' => $mergeToken,
            ])
            ->assertStatus(JsonResponse::HTTP_FORBIDDEN);
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testMergeAccountExpiredToken($key): void
    {
        $this->mockSocialiteUser($key);

        AuthProviderModel::query()
            ->where('key', $key)
            ->update([
                'active' => true,
                'client_id' => 'test_id',
                'client_secret' => 'test_secret',
            ]);

        $existingUser = User::factory()->create([
            'name' => 'test user',
            'email' => 'test.user@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $mergeToken = Str::random(128);
        $existingUser->providers()->create([
            'provider' => $key,
            'provider_user_id' => 123456789,
            'user_id' => $existingUser->getKey(),
            'merge_token' => $mergeToken,
            'merge_token_expires_at' => Carbon::now()->subDay(),
        ]);

        $this
            ->actingAs($existingUser)
            ->json('POST', '/auth/providers/merge-account', [
                'merge_token' => $mergeToken,
            ])
            ->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment(['message' => Exceptions::CLIENT_PROVIDER_MERGE_TOKEN_EXPIRED]);

        $this->assertDatabaseHas('user_providers', [
            'provider' => $key,
            'user_id' => $existingUser->getKey(),
            'merge_token' => $mergeToken,
        ]);
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testMergeAccountDifferentUser($key): void
    {
        $this->mockSocialiteUser($key);

        AuthProviderModel::query()
            ->where('key', $key)
            ->update([
                'active' => true,
                'client_id' => 'test_id',
                'client_secret' => 'test_secret',
            ]);

        $existingUser = User::factory()->create([
            'name' => 'test user',
            'email' => 'test.user@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $mergeToken = Str::random(128);
        $existingUser->providers()->create([
            'provider' => $key,
            'provider_user_id' => 123456789,
            'user_id' => $existingUser->getKey(),
            'merge_token' => $mergeToken,
            'merge_token_expires_at' => Carbon::now()->subDay(),
        ]);

        $this
            ->actingAs($this->user)
            ->json('POST', '/auth/providers/merge-account', [
                'merge_token' => $mergeToken,
            ])
            ->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseHas('user_providers', [
            'provider' => $key,
            'user_id' => $existingUser->getKey(),
            'merge_token' => $mergeToken,
        ]);
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testMergeAccountInvalidMergeToken($key): void
    {
        $this->mockSocialiteUser($key);

        AuthProviderModel::query()
            ->where('key', $key)
            ->update([
                'active' => true,
                'client_id' => 'test_id',
                'client_secret' => 'test_secret',
            ]);

        $existingUser = User::factory()->create([
            'name' => 'test user',
            'email' => 'test.user@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $mergeToken = Str::random(128);
        $existingUser->providers()->create([
            'provider' => $key,
            'provider_user_id' => 123456789,
            'user_id' => $existingUser->getKey(),
            'merge_token' => $mergeToken,
            'merge_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $invalidMergeToken = Str::random(128);
        $this
            ->actingAs($existingUser)
            ->json('POST', '/auth/providers/merge-account', [
                'merge_token' => $invalidMergeToken,
            ])
            ->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseHas('user_providers', [
            'provider' => $key,
            'user_id' => $existingUser->getKey(),
            'merge_token' => $mergeToken,
        ]);
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testMergeAccount($key): void
    {
        $this->mockSocialiteUser($key);

        AuthProviderModel::query()
            ->where('key', $key)
            ->update([
                'active' => true,
                'client_id' => 'test_id',
                'client_secret' => 'test_secret',
            ]);

        $existingUser = User::factory()->create([
            'name' => 'test user',
            'email' => 'test.user@gmail.com',
            'password' => Hash::make('password'),
        ]);

        $mergeToken = Str::random(128);
        $existingUser->providers()->create([
            'provider' => $key,
            'provider_user_id' => 123456789,
            'user_id' => $existingUser->getKey(),
            'merge_token' => $mergeToken,
            'merge_token_expires_at' => Carbon::now()->addDay(),
        ]);

        $this
            ->actingAs($existingUser)
            ->json('POST', '/auth/providers/merge-account', [
                'merge_token' => $mergeToken,
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('user_providers', [
            'provider' => $key,
            'user_id' => $existingUser->getKey(),
            'merge_token' => null,
        ]);
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testStandardRegistrationAfterRegisteredWithProvider($key): void
    {
        $this->mockSocialiteUser($key);

        AuthProviderModel::query()
            ->where('key', $key)
            ->update([
                'active' => true,
                'client_id' => 'test_id',
                'client_secret' => 'test_secret',
            ]);

        $existingUser = User::factory()->create([
            'name' => 'test user',
            'email' => 'test.user@gmail.com',
            'password' => null,
        ]);

        $existingUser->providers()->create([
            'provider' => $key,
            'provider_user_id' => 123456789,
            'user_id' => $existingUser->getKey(),
        ]);

        Notification::fake();

        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $this->json('POST', '/register', [
            'name' => 'test user',
            'email' => 'test.user@gmail.com',
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Notification::assertNothingSent();
    }

    private function mockSocialiteUser(string $key): void
    {
        $user = Mockery::mock('Laravel\Socialite\Two\User');
        $user
            ->shouldReceive('getId')
            ->andReturn(123456789)
            ->shouldReceive('getName')
            ->andReturn('test user')
            ->shouldReceive('getEmail')
            ->andReturn('test.user@gmail.com')
            ->shouldReceive('getAvatar')
            ->andReturn('https://en.gravatar.com/userimage');

        $provider = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider
            ->shouldReceive('stateless')
            ->andReturn($provider)
            ->shouldReceive('user')
            ->andReturn($user);

        Socialite::shouldReceive('driver')->with($key)->andReturn($provider);
    }
}
