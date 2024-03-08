<?php

namespace Tests\Feature\Auth;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\TokenType;
use Tests\TestCase;

class AuthRefreshTokenTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testRefreshTokenMissing(string $user): void
    {
        $response = $this->actingAs($this->{$user})->postJson('/auth/refresh', [
            'refresh_token' => null,
        ]);

        $response->assertUnprocessable();
    }

    public function testRefreshTokenAfterUserDeleted(): void
    {
        $token = $this->tokenService->createToken(
            $this->user,
            TokenType::REFRESH,
        );

        $response = $this->actingAs($this->user)->json('POST', 'auth/refresh', [
            'refresh_token' => $token,
        ]);

        $this->user->delete();

        $responseFail = $this->json('POST', 'auth/refresh', [
            'refresh_token' => $response->getData()->data->refresh_token,
        ]);

        $responseFail->assertStatus(422)
            ->assertJsonFragment(['key' => Exceptions::CLIENT_USER_DOESNT_EXIST->name]);
    }

    public function testRefreshTokenUser(): void
    {
        $token = $this->tokenService->createToken(
            $this->user,
            TokenType::REFRESH,
        );

        $response = $this->actingAs($this->user)->postJson('/auth/refresh', [
            'refresh_token' => $token,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
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
        $token = $this->tokenService->createToken(
            $this->application,
            TokenType::REFRESH,
        );

        $response = $this->actingAs($this->application)->postJson('/auth/refresh', [
            'refresh_token' => $token,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
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
    public function testRefreshTokenInvalidated(string $user): void
    {
        $token = $this->tokenService->createToken(
            $this->{$user},
            TokenType::REFRESH,
        );
        $this->tokenService->invalidateToken($token);

        $response = $this->actingAs($this->{$user})->postJson('/auth/refresh', [
            'refresh_token' => $token,
        ]);

        $response->assertStatus(422);
    }
}
