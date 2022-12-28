<?php

namespace Tests\Feature;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\ValidationError;
use App\Models\AuthProvider as AuthProviderModel;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class ProviderTest extends TestCase
{
    public function socialMediaProvider(): array
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
        AuthProviderModel::factory()->create([
            'key' => 'facebook',
            'active' => true,
        ]);

        $this->getJson('auth/providers')
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
        AuthProviderModel::factory()->create([
            'key' => 'facebook',
            'active' => true,
        ]);

        $this->getJson('auth/providers?active=true')
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    0 => [
                        'key' => 'facebook',
                        'active' => true,
                    ],
                ],
            ]);
    }

    public function testProvidersListInactive(): void
    {
        AuthProviderModel::factory()->create([
            'key' => 'facebook',
            'active' => true,
        ]);

        $this->getJson('auth/providers?active=false')
            ->assertJson([
                'data' => [
                    0 => [
                        'key' => 'google',
                        'active' => false,
                    ],
                    1 => [
                        'key' => 'apple',
                        'active' => false,
                    ],
                    2 => [
                        'key' => 'github',
                        'active' => false,
                    ],
                    3 => [
                        'key' => 'gitlab',
                        'active' => false,
                    ],
                    4 => [
                        'key' => 'bitbucket',
                        'active' => false,
                    ],
                    5 => [
                        'key' => 'linkedin',
                        'active' => false,
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testGetProvider($user): void
    {
        $this->$user->givePermissionTo('auth.providers.manage');

        $provider = AuthProviderModel::factory()->create([
            'key' => 'facebook',
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('auth/providers/facebook')
            ->assertJson([
                'data' => [
                    'id' => $provider->getKey(),
                    'key' => $provider->key,
                    'active' => $provider->active,
                    'client_id' => $provider->client_id,
                    'client_secret' => $provider->client_secret,
                ],
            ]);
    }

    public function testGetProviderWithoutPermission(): void
    {
        $provider = AuthProviderModel::factory()->create([
            'key' => 'facebook',
        ]);

        $this
            ->getJson('auth/providers/facebook')
            ->assertJson([
                'data' => [
                    'id' => $provider->getKey(),
                    'key' => $provider->key,
                    'active' => $provider->active,
                ],
            ])
            ->assertJsonMissing([
                'client_id' => $provider->client_id,
                'client_secret' => $provider->client_secret,
            ]);
    }

    public function testUpdateUnauthorized(): void
    {
        AuthProviderModel::factory()->create([
            'key' => 'facebook',
            'active' => false,
        ]);

        $this->json('patch', 'auth/providers/facebook', [
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
        $this->$user->givePermissionTo('auth.providers.manage');

        AuthProviderModel::factory()->create([
            'key' => 'facebook',
            'active' => false,
        ]);

        $response = $this->actingAs($this->$user)->json('patch', 'auth/providers/facebook', [
            'active' => true,
            'client_id' => 'test_id',
            'client_secret' => 'test_secret',
        ]);

        $response->assertOk()
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
        $this->$user->givePermissionTo('auth.providers.manage');

        AuthProviderModel::factory()->create([
            'key' => 'facebook',
            'active' => false,
            'client_id' => null,
            'client_secret' => null,
        ]);

        $this->actingAs($this->$user)->json('patch', 'auth/providers/facebook', [
            'active' => true,
        ])
            ->assertJsonFragment(['key' => ValidationError::AUTHPROVIDERACTIVE]);
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testRedirect($key): void
    {
        $provider = AuthProviderModel::factory()->create([
            'key' => $key,
            'active' => true,
            'client_secret' => '***REMOVED***',
            'client_id' => '***REMOVED***',
        ]);

        $response = $this->json('post', "auth/providers/{$provider->key}/redirect", [
            'return_url' => 'http://localhost',
        ]);

        $response->assertOk();
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testRedirectInactive($key): void
    {
        $provider = AuthProviderModel::factory()->create([
            'key' => $key,
            'active' => false,
        ]);

        $response = $this->json('post', "auth/providers/{$provider->key}/redirect", [
            'return_url' => 'http://localhost',
        ]);

        $response->assertJsonFragment([
            'key' => Exceptions::getKey(Exceptions::CLIENT_PROVIDER_IS_NOT_ACTIVE),
        ]);
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testRedirectNoConfig($key): void
    {
        $provider = AuthProviderModel::factory()->create([
            'key' => $key,
            'client_id' => null,
            'client_secret' => null,
            'active' => false,
        ]);

        $response = $this->json('post', "auth/providers/{$provider->key}/redirect", [
            'return_url' => 'http://localhost',
        ]);

        $response->assertJsonFragment([
            'key' => Exceptions::getKey(Exceptions::CLIENT_PROVIDER_HAS_NO_CONFIG),
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
            ]
        )->assertForbidden();
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testLoginNewUser($key): void
    {
        $this->user->givePermissionTo(['auth.register']);
        $user = \Mockery::mock('Laravel\Socialite\Two\User');
        $user
            ->shouldReceive('getId')
            ->andReturn(rand())
            ->shouldReceive('getName')
            ->andReturn('test user')
            ->shouldReceive('getEmail')
            ->andReturn('test.user@gmail.com')
            ->shouldReceive('getAvatar')
            ->andReturn('https://en.gravatar.com/userimage');

        $provider = \Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider
            ->shouldReceive('stateless')
            ->andReturn($provider)
            ->shouldReceive('user')
            ->andReturn($user);

        Socialite::shouldReceive('driver')->with($key)->andReturn($provider);

        AuthProviderModel::factory()->create([
            'key' => $key,
            'active' => true,
            'client_id' => 'test_id',
            'client_secret' => 'test_secret',
        ]);

        $response = $this->actingAs($this->user)->json(
            'POST',
            "auth/providers/{$key}/login",
            [
                'return_url' => 'https://example.com?code=test',
            ]
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
            ]
        );
    }

    /**
     * @dataProvider socialMediaProvider
     */
    public function testLoginExistingUser($key): void
    {
        $this->user->givePermissionTo(['auth.register']);
        $user = \Mockery::mock('Laravel\Socialite\Two\User');
        $user
            ->shouldReceive('getId')
            ->andReturn(123456789)
            ->shouldReceive('getName')
            ->andReturn('test user')
            ->shouldReceive('getEmail')
            ->andReturn('test.user@gmail.com')
            ->shouldReceive('getAvatar')
            ->andReturn('https://en.gravatar.com/userimage');

        $provider = \Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $provider
            ->shouldReceive('stateless')
            ->andReturn($provider)
            ->shouldReceive('user')
            ->andReturn($user);

        Socialite::shouldReceive('driver')->with($key)->andReturn($provider);

        $authProvider = AuthProviderModel::factory()->create([
            'key' => $key,
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
            'provider' => $authProvider->key,
            'provider_user_id' => 123456789,
            'user_id' => $existingUser->getKey(),
        ]);

        $response = $this->actingAs($this->user)->json(
            'POST',
            "auth/providers/{$key}/login",
            [
                'return_url' => 'https://example.com?code=test',
            ]
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
            ]
        );
    }
}
