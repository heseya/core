<?php

namespace Tests\Feature;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\IssuerType;
use App\Enums\RoleType;
use App\Enums\TFAType;
use App\Enums\TokenType;
use App\Events\FailedLoginAttempt;
use App\Events\NewLocalizationLoginAttempt;
use App\Events\PasswordReset;
use App\Events\SuccessfulLoginAttempt;
use App\Events\TfaInit;
use App\Events\TfaRecoveryCodesChanged;
use App\Events\TfaSecurityCode as TfaSecurityCodeEvent;
use App\Listeners\WebHookEventListener;
use App\Models\App;
use App\Models\OneTimeSecurityCode;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserLoginAttempt;
use App\Models\UserPreference;
use App\Models\WebHook;
use App\Notifications\ResetPassword;
use App\Notifications\TFAInitialization;
use App\Notifications\TFARecoveryCodes;
use App\Notifications\TFASecurityCode;
use App\Notifications\UserRegistered;
use App\Services\Contracts\OneTimeSecurityCodeContract;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use PHPGangsta_GoogleAuthenticator;
use Spatie\WebhookServer\CallWebhookJob;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use WithFaker;

    private string $expectedLog;
    private OneTimeSecurityCodeContract $oneTimeSecurityCodeService;
    private array $expected;
    private string $cipher;
    private string $webhookKey;

    public static function tfaMethodProvider(): array
    {
        return [
            'as app 2fa' => [TFAType::APP, 'secret'],
            'as email 2fa' => [TFAType::EMAIL, null],
        ];
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->user->preferences()->associate(
            UserPreference::create([
                'failed_login_attempt_alert' => false,
                'new_localization_login_alert' => false,
                'recovery_code_changed_alert' => false,
            ]),
        );
        $this->user->save();

        $this->expectedLog = 'ClientException(code: 422): Invalid credentials at';
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
                'roles',
                'shipping_addresses',
                'billing_addresses',
                'permissions',
                'created_at',
            ],
        ];

        $this->cipher = Config::get('webhook.cipher');
        $this->webhookKey = Config::get('webhook.key');
    }

    public function testSuccessfulLoginWithoutPreferences(): void
    {
        /** @var User $user */
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this
            ->actingAs($user)
            ->json('POST', '/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ])
            ->assertOk();
    }

    public function testUserAgentLength(): void
    {
        $userAgent = 'Mozilla/5.0 (Linux; Android 10; moto e(7) power Build/QOMS30.288-52-10; wv)
         AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/105.0.5195.136
         Mobile Safari/537.36 Instagram 255.1.0.17.102
         Android (29/10; 280dpi; 720x1472; motorola; moto e(7) power; malta; mt6762; pl_PL; 405431925)';
        $this
            ->actingAs($this->user)->json(
                'POST',
                '/login',
                [
                    'email' => $this->user->email,
                    'password' => $this->password,
                ],
                [
                    'User-Agent' => $userAgent,
                ],
            )
            ->assertOk()
            ->assertJsonStructure(['data' => $this->expected]);

        $this->assertDatabaseHas('user_login_attempts', [
            'user_id' => $this->user->getKey(),
            'user_agent' => $userAgent,
        ]);
    }

    public function testSuccessfulLoginAttempt(): array
    {
        $this->user->preferences()->update([
            'successful_login_attempt_alert' => true,
        ]);

        Event::fake([SuccessfulLoginAttempt::class]);

        $this
            ->actingAs($this->user)->postJson('/login', [
                'email' => $this->user->email,
                'password' => $this->password,
            ])
            ->assertOk()
            ->assertJsonStructure(['data' => $this->expected]);

        $attempt = UserLoginAttempt::where('user_id', $this->user->id)->latest()->first();

        Event::assertDispatched(SuccessfulLoginAttempt::class);

        return [$this->user, $attempt, new SuccessfulLoginAttempt($attempt)];
    }

    /**
     * @depends testSuccessfulLoginAttempt
     */
    public function testSuccessfulLoginAttemptWebhookDispatch($payload): void
    {
        $webHook = WebHook::factory()->create([
            'events' => [
                'SuccessfulLoginAttempt',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        [$user, $attempt, $event] = $payload;

        $attempt->user()->associate($user);

        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $user, $attempt) {
            $payload = $job->payload;

            $data = $this->decryptData($payload['data']);

            if (!$data) {
                return false;
            }

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $data['user']['id'] === $user->getKey()
                && $data['user_agent'] === $attempt->user_agent
                && $data['ip'] === $attempt->ip
                && $payload['data_type'] === 'LocalizedLoginAttempt'
                && $payload['event'] === 'SuccessfulLoginAttempt'
                && $payload['issuer_type'] === IssuerType::USER;
        });
    }

    public function testSuccessfulLoginNewLocalization(): array
    {
        $this->user->preferences()->update([
            'new_localization_login_alert' => true,
        ]);

        Event::fake([NewLocalizationLoginAttempt::class]);

        $this
            ->actingAs($this->user)->postJson('/login', [
                'email' => $this->user->email,
                'password' => $this->password,
            ])
            ->assertOk()
            ->assertJsonStructure(['data' => $this->expected]);

        $attempt = UserLoginAttempt::where('user_id', $this->user->id)->latest()->first();

        Event::assertDispatched(NewLocalizationLoginAttempt::class);

        return [$this->user, $attempt, new NewLocalizationLoginAttempt($attempt)];
    }

    public function testFailedLoginNewLocalization(): array
    {
        $this->user->preferences()->update([
            'new_localization_login_alert' => true,
        ]);

        Event::fake([NewLocalizationLoginAttempt::class]);

        $this
            ->actingAs($this->user)->postJson('/login', [
                'email' => $this->user->email,
                'password' => 'bad-password',
            ]);

        $attempt = UserLoginAttempt::where('user_id', $this->user->id)->latest()->first();

        Event::assertDispatched(NewLocalizationLoginAttempt::class);

        return [$this->user, $attempt, new NewLocalizationLoginAttempt($attempt)];
    }

    /**
     * @depends testSuccessfulLoginNewLocalization
     * @depends testFailedLoginNewLocalization
     */
    public function testNewLocalizationWebhookDispatch($payload1, $payload2): void
    {
        $webHook = WebHook::factory()->create([
            'events' => [
                'NewLocalizationLoginAttempt',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        [$user, $attempt, $event] = $payload1;
        [$userFailed, $attemptFailed, $eventFailed] = $payload2;

        $attempt->user()->associate($user);

        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $user, $attempt) {
            $payload = $job->payload;

            $data = $this->decryptData($payload['data']);

            if (!$data) {
                return false;
            }

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $data['user']['id'] === $user->getKey()
                && $data['user_agent'] === $attempt->user_agent
                && $data['ip'] === $attempt->ip
                && $payload['data_type'] === 'LocalizedLoginAttempt'
                && $payload['event'] === 'NewLocalizationLoginAttempt'
                && $payload['issuer_type'] === IssuerType::USER;
        });

        $attemptFailed->user()->associate($userFailed);

        $listener = new WebHookEventListener();

        $listener->handle($eventFailed);

        Bus::assertDispatched(
            CallWebhookJob::class,
            function ($job) use ($webHook, $userFailed, $attemptFailed) {
                $payload = $job->payload;

                $data = $this->decryptData($payload['data']);

                if (!$data) {
                    return false;
                }

                return $job->webhookUrl === $webHook->url
                    && isset($job->headers['Signature'])
                    && $data['user']['id'] === $userFailed->getKey()
                    && $data['user_agent'] === $attemptFailed->user_agent
                    && $data['ip'] === $attemptFailed->ip
                    && $payload['data_type'] === 'LocalizedLoginAttempt'
                    && $payload['event'] === 'NewLocalizationLoginAttempt'
                    && $payload['issuer_type'] === IssuerType::USER;
            },
        );
    }

    public function testLoginInvalidCredential(): void
    {
        Event::fake([FailedLoginAttempt::class]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, $this->expectedLog));

        $this
            ->actingAs($this->user)
            ->json('POST', '/login', [
                'email' => $this->user->email,
                'password' => 'bad-password',
            ])
            ->assertUnprocessable();

        Event::assertNotDispatched(FailedLoginAttempt::class);
    }

    public function testFailedLoginAttempt(): array
    {
        $this->user->preferences()->update([
            'failed_login_attempt_alert' => true,
        ]);

        Event::fake([FailedLoginAttempt::class]);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, $this->expectedLog));

        $this
            ->actingAs($this->user)
            ->json('POST', '/login', [
                'email' => $this->user->email,
                'password' => 'bad-password',
            ])
            ->assertUnprocessable();

        $attempt = UserLoginAttempt::where('user_id', $this->user->getKey())->latest()->first();

        Event::assertDispatched(FailedLoginAttempt::class);

        return [$this->user, $attempt, new FailedLoginAttempt($attempt)];
    }

    /**
     * @depends testFailedLoginAttempt
     */
    public function testFailedLoginAttemptWebhookDispatch($payload): void
    {
        $webHook = WebHook::factory()->create([
            'events' => [
                'FailedLoginAttempt',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        [$user, $attempt, $event] = $payload;

        $attempt->user()->associate($user);

        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $user, $attempt) {
            $payload = $job->payload;

            $data = $this->decryptData($payload['data']);

            if (!$data) {
                return false;
            }

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $data['user']['id'] === $user->getKey()
                && $data['user_agent'] === $attempt->user_agent
                && $data['ip'] === $attempt->ip
                && $payload['data_type'] === 'LocalizedLoginAttempt'
                && $payload['event'] === 'FailedLoginAttempt'
                && $payload['issuer_type'] === IssuerType::USER;
        });
    }

    public function testLoginDisabledTfaCode(): void
    {
        $this
            ->actingAs($this->user)
            ->json('POST', '/login', [
                'email' => $this->user->email,
                'password' => $this->password,
                'code' => 'code',
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::coerce(Exceptions::CLIENT_TFA_NOT_SET_UP)->key,
            ]);
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testLoginEnabledTfaNoCode($method, $secret): void
    {
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
                'key' => Exceptions::getKey(Exceptions::CLIENT_TFA_REQUIRED),
            ]);
    }

    public function testLoginEnabledTfaAppNoCodeWebhookEvent(): void
    {
        Event::fake([TFASecurityCode::class]);

        $this->user->update([
            'tfa_type' => TFAType::APP,
            'tfa_secret' => 'secret',
            'is_tfa_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson('/login', [
            'email' => $this->user->email,
            'password' => $this->password,
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        Event::assertNotDispatched(TFASecurityCode::class);
    }

    public function testLoginEnabledTfaEmailNoCodeWebhookEvent(): array
    {
        Event::fake([TfaSecurityCodeEvent::class]);
        Notification::fake();

        $this->user->update([
            'tfa_type' => TFAType::EMAIL,
            'tfa_secret' => null,
            'is_tfa_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson('/login', [
            'email' => $this->user->email,
            'password' => $this->password,
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $code = OneTimeSecurityCode::where('user_id', $this->user->id)->first()->code;

        Event::assertDispatched(TfaSecurityCodeEvent::class);

        return [$this->user, $code, new TfaSecurityCodeEvent($this->user, $code)];
    }

    /**
     * @depends testLoginEnabledTfaEmailNoCodeWebhookEvent
     */
    public function testLoginEnabledTfaNoCodeWebhookDispatch($payload): void
    {
        $webHook = WebHook::factory()->create([
            'events' => [
                'TfaSecurityCode',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        [$user, $code, $event] = $payload;

        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $user, $code) {
            $payload = $job->payload;

            $data = $this->decryptData($payload['data']);

            if (!$data) {
                return false;
            }

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $data['user']['id'] === $user->getKey()
                && $data['security_code'] === $code
                && $payload['data_type'] === 'TfaCode'
                && $payload['event'] === 'TfaSecurityCode'
                && $payload['issuer_type'] === IssuerType::USER;
        });
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testLoginEnabledTfaCode($method, $secret): void
    {
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
                'key' => Exceptions::coerce(Exceptions::CLIENT_TFA_INVALID_TOKEN)->key,
            ]);

        $this->assertDatabaseCount('one_time_security_codes', 1);
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testLoginEnabledTfaRecoveryCode($method, $secret): void
    {
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
                'key' => Exceptions::coerce(Exceptions::CLIENT_TFA_INVALID_TOKEN)->key,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testRefreshTokenMissing($user): void
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
            new TokenType(TokenType::REFRESH),
        );

        $response = $this->actingAs($this->user)->json('POST', 'auth/refresh', [
            'refresh_token' => $token,
        ]);

        $this->user->delete();

        $responseFail = $this->json('POST', 'auth/refresh', [
            'refresh_token' => $response->getData()->data->refresh_token,
        ]);

        $responseFail->assertStatus(422)
            ->assertJsonFragment(['key' => Exceptions::fromValue(Exceptions::CLIENT_USER_DOESNT_EXIST)->key]);
    }

    public function testRefreshTokenUser(): void
    {
        $token = $this->tokenService->createToken(
            $this->user,
            new TokenType(TokenType::REFRESH),
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
            new TokenType(TokenType::REFRESH),
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
    public function testRefreshTokenInvalidated($user): void
    {
        $token = $this->tokenService->createToken(
            $this->{$user},
            new TokenType(TokenType::REFRESH),
        );
        $this->tokenService->invalidateToken($token);

        $response = $this->actingAs($this->{$user})->postJson('/auth/refresh', [
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
                $this->defaultHeaders + ['Authorization' => 'Bearer ' . $token],
            );

        $this
            ->json(
                'POST',
                '/auth/logout',
                [],
                $this->defaultHeaders + ['Authorization' => 'Bearer ' . $token],
            )
            ->assertStatus(422);
    }

    public function testResetPasswordUnauthorized(): void
    {
        $email = $this->faker->unique()->safeEmail;
        $password = 'Passwd###111';

        $user = User::factory()->create([
            'name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
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

    public function testResetPassword11(): void
    {
        $this->user->givePermissionTo('auth.password_reset');

        $email = $this->faker->unique()->safeEmail;
        $password = 'Passwd###111';

        $user = User::factory()->create([
            'name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        Notification::fake();

        $response = $this->actingAs($this->user)->postJson('/users/reset-password', [
            'email' => $user->email,
            'redirect_url' => 'https://test.com',
        ]);

        Notification::assertSentTo($user, ResetPassword::class);

        $response->assertNoContent();
    }

    public function testResetPasswordWebhookEvent(): array
    {
        $this->user->givePermissionTo('auth.password_reset');

        $email = $this->faker->unique()->safeEmail;
        $password = 'Passwd###111';

        $user = User::factory()->create([
            'name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        Mail::fake();
        Event::fake([PasswordReset::class]);

        $this
            ->actingAs($this->user)
            ->json('POST', '/users/reset-password', [
                'email' => $user->email,
                'redirect_url' => 'https://example.com',
            ])
            ->assertNoContent();

        Event::assertDispatched(PasswordReset::class);

        $passwordResetData = DB::table('password_resets')
            ->where('email', $user->email)
            ->first();

        $param = http_build_query([
            'token' => $passwordResetData->token,
            'email' => $passwordResetData->email,
        ]);

        $url = Config::get('app.admin_url') . '/password/reset?' . $param;

        return [$user, $url, new PasswordReset($user, $url)];
    }

    /**
     * @depends testResetPasswordWebhookEvent
     */
    public function testResetPasswordWebhookDispatch($payload): void
    {
        $webHook = WebHook::factory()->create([
            'events' => [
                'PasswordReset',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        [$user, $url, $event] = $payload;

        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $user, $url) {
            $payload = $job->payload;

            $data = $this->decryptData($payload['data']);

            if (!$data) {
                return false;
            }

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $data['user']['id'] === $user->getKey()
                && $data['recovery_url'] === $url
                && $payload['data_type'] === 'PasswordRecovery'
                && $payload['event'] === 'PasswordReset'
                && $payload['issuer_type'] === IssuerType::USER;
        });
    }

    public function testResetPasswordDifferentEmail(): void
    {
        $this->user->givePermissionTo('auth.password_reset');

        $email = $this->faker->unique()->safeEmail;
        $password = 'Passwd###111';

        User::factory()->create([
            'name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        Mail::fake();
        Event::fake([PasswordReset::class]);
        Mail::assertNothingSent();

        $response = $this->actingAs($this->user)->postJson('/users/reset-password', [
            'email' => $this->faker->unique()->safeEmail,
            'redirect_url' => 'https://test.com',
        ]);

        Mail::assertNothingSent();

        $response->assertNoContent();

        Event::assertNotDispatched(PasswordReset::class);
    }

    public function testSaveResetPasswordUnauthorized(): void
    {
        $email = $this->faker->unique()->safeEmail;
        $newPassword = 'NewPasswd###111';

        $user = User::factory()->create([
            'name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email' => $email,
        ]);

        $token = Password::createToken($user);

        $response = $this->putJson('/users/save-reset-password', [
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
            'name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email' => $email,
        ]);

        $token = Password::createToken($user);
        $this->assertTrue(Password::tokenExists($user, $token));

        $this->actingAs($this->user)->putJson('/users/save-reset-password', [
            'email' => $email,
            'password' => $newPassword,
            'token' => $token,
        ]);

        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Password::tokenExists($user, $token));
    }

    public function testShowResetPasswordForm(): void
    {
        $this->user->givePermissionTo('auth.password_reset');

        $token = Password::createToken($this->user);
        $response = $this->actingAs($this->user)
            ->json('get', '/users/reset-password/' . $token . '/' . $this->user->email);
        $response->assertOk();
    }

    public function testSaveResetPasswordInvalidToken(): void
    {
        $this->user->givePermissionTo('auth.password_reset');

        $email = $this->faker->unique()->safeEmail;
        $newPassword = 'NewPasswd###111';

        $user = User::factory()->create([
            'name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email' => $email,
        ]);

        $token = Password::createToken($user);
        $this->assertTrue(Password::tokenExists($user, $token));

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message) {
                return str_contains(
                    $message,
                    'ClientException(code: 422): The token is invalid or inactive. Try to reset your password again',
                );
            });

        $response = $this->actingAs($this->user)->json(
            'PUT',
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

        $response = $this->actingAs($user)->putJson('/users/password', [
            'password' => 'test',
            'password_new' => 'Test1@3456',
        ]);

        $response->assertForbidden();
    }

    public function testChangePasswordNoUser(): void
    {
        $this->putJson('/users/password', [
            'password' => 'test',
            'password_new' => 'Test1@3456',
        ])->assertForbidden();
    }

    public function testChangePassword(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('test'),
        ]);

        $user->givePermissionTo('auth.password_change');

        $response = $this->actingAs($user)->putJson('/users/password', [
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
            ->withArgs(
                fn ($message) => str_contains(
                    $message,
                    'App\Exceptions\ClientException(code: 422): Invalid password at',
                ),
            );

        $response = $this->actingAs($user)->json('PUT', '/users/password', [
            'password' => 'tests',
            'password_new' => 'Test1@345678',
        ]);

        $response->assertStatus(422);
    }

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
            ->assertJson([
                'data' => [
                    'id' => $user->getKey(),
                    'email' => $user->email,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'roles' => [
                        [
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
                    'metadata' => [],
                    'metadata_personal' => [],
                    'created_at' => $user->created_at,
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
            ->assertJson([
                'data' => [
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

    public function testUpdateProfile(): void
    {
        $user = User::factory()->create();
        $user->preferences()->associate(UserPreference::create());
        $user->save();

        $this->actingAs($user)->json('PATCH', '/auth/profile', [
            'birthday_date' => '1990-01-01',
            'phone' => '+48123456789',
            'preferences' => [
                'successful_login_attempt_alert' => true,
                'failed_login_attempt_alert' => false,
                'new_localization_login_alert' => false,
                'recovery_code_changed_alert' => false,
            ],
        ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $user->getKey(),
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'birthday_date' => '1990-01-01',
                'phone' => '+48123456789',
                'phone_country' => 'PL',
                'phone_number' => '12 345 67 89',
            ])
            ->assertJsonFragment([
                'successful_login_attempt_alert' => true,
                'failed_login_attempt_alert' => false,
                'new_localization_login_alert' => false,
                'recovery_code_changed_alert' => false,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'birthday_date' => '1990-01-01',
            'phone_number' => '12 345 67 89',
            'phone_country' => 'PL',
        ]);
        $this->assertDatabaseCount('user_preferences', 2); // +1 for $this->user
        $this->assertDatabaseHas('user_preferences', [
            'id' => $user->preferences_id,
            'successful_login_attempt_alert' => true,
            'failed_login_attempt_alert' => false,
            'new_localization_login_alert' => false,
            'recovery_code_changed_alert' => false,
        ]);
    }

    public function testSelfUpdateRolesUnauthorized(): void
    {
        $role = Role::factory()->create([
            'is_joinable' => true,
        ]);

        $this->json('PATCH', '/auth/profile/roles', [
            'roles' => [
                $role->getKey(),
            ],
        ])
            ->assertForbidden();
    }

    public function testSelfUpdateRoles(): void
    {
        $role = Role::factory()->create([
            'name' => 'New joinable role',
            'type' => RoleType::REGULAR,
            'is_joinable' => true,
        ]);

        $noJoinable = Role::factory()->create([
            'name' => 'No joinable role',
            'type' => RoleType::REGULAR,
            'is_joinable' => false,
        ]);

        $this->user->roles()->attach($noJoinable->getKey());

        $this->actingAs($this->user)->json('PATCH', '/auth/profile/roles', [
            'roles' => [
                $role->getKey(),
            ],
        ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $noJoinable->getKey(),
                'name' => 'No joinable role',
                'is_joinable' => false,
            ])
            ->assertJsonFragment([
                'id' => $role->getKey(),
                'name' => 'New joinable role',
                'is_joinable' => true,
            ]);
    }

    public function testSelfUpdateRolesNoJoinable(): void
    {
        $role = Role::factory()->create([
            'name' => 'New joinable role',
            'type' => RoleType::REGULAR,
            'is_joinable' => false,
        ]);

        $this->actingAs($this->user)->json('PATCH', '/auth/profile/roles', [
            'roles' => [
                $role->getKey(),
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::coerce(Exceptions::CLIENT_JOINING_NON_JOINABLE_ROLE)->key,
            ])->assertJsonFragment([
                'message' => Exceptions::CLIENT_JOINING_NON_JOINABLE_ROLE,
            ]);
    }

    public function testSelfUpdateRolesRemove(): void
    {
        $role = Role::factory()->create([
            'name' => 'New joinable role',
            'type' => RoleType::REGULAR,
            'is_joinable' => true,
        ]);

        $noJoinable = Role::factory()->create([
            'name' => 'No joinable role',
            'type' => RoleType::REGULAR,
            'is_joinable' => false,
        ]);

        $this->user->roles()->saveMany([$role, $noJoinable]);

        $this->actingAs($this->user)->json('PATCH', '/auth/profile/roles', [
            'roles' => [],
        ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $noJoinable->getKey(),
                'name' => 'No joinable role',
                'is_joinable' => false,
            ])
            ->assertJsonMissing([
                'id' => $role->getKey(),
                'name' => 'New joinable role',
                'is_joinable' => true,
            ]);
    }

    public function testCheckIdentityUnauthorized(): void
    {
        $user = User::factory()->create();

        $token = $this->tokenService->createToken(
            $user,
            new TokenType(TokenType::IDENTITY),
        );

        $this->actingAs($user)->getJson("/auth/check/{$token}")
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCheckIdentityInvalidToken($user): void
    {
        $this->{$user}->givePermissionTo('auth.check_identity');

        $token = $this->tokenService->createToken(
            User::factory()->create(),
            new TokenType(TokenType::IDENTITY),
        ) . 'invalid_hash';

        $this
            ->actingAs($this->{$user})
            ->json('GET', "/auth/check/{$token}")
            ->assertStatus(422);

        $this->actingAs($this->{$user})
            ->json('GET', '/auth/check/its-not-real-token')
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCheckIdentityNoToken($user): void
    {
        $this->{$user}->givePermissionTo('auth.check_identity');

        $this->actingAs($this->{$user})->getJson('/auth/check')
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
        $this->{$user}->givePermissionTo('auth.check_identity');

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

        $this->actingAs($this->{$user})->getJson("/auth/check/{$token}")
            ->assertOk()
            ->assertJson([
                'data' => [
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

        $this->{$user}->givePermissionTo('auth.check_identity');

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

        $this->actingAs($this->{$user})->getJson("/auth/check/{$token}")
            ->assertOk()
            ->assertJson([
                'data' => [
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

        $this->actingAs($app)->getJson("/auth/check/{$token}")
            ->assertOk()
            ->assertJson([
                'data' => [
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
            ->assertJsonFragment([
                'key' => Exceptions::coerce(Exceptions::CLIENT_TFA_ALREADY_SET_UP)->key,
            ]);
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
        Event::fake([TfaInit::class]);

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
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'secret',
                    'qr_code_url',
                ],
            ]);

        Event::assertNotDispatched(TfaInit::class);
    }

    public function testConfirmAppTfa(): void
    {
        $this->user->preferences()->update([
            'recovery_code_changed_alert' => true,
        ]);
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
        ])->assertOk()->assertJsonStructure([
            'data' => [
                'recovery_codes',
            ],
        ]);

        Notification::assertSentTo(
            [$this->user],
            TFARecoveryCodes::class,
        );

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'is_tfa_active' => true,
        ]);

        $this->assertDatabaseCount('one_time_security_codes', 3);
    }

    public function testConfirmAppTfaWebhookEvent(): array
    {
        $this->user->preferences()->update([
            'recovery_code_changed_alert' => true,
        ]);
        Notification::fake();
        Event::fake([TfaRecoveryCodesChanged::class]);

        $google_authenticator = new PHPGangsta_GoogleAuthenticator();

        $secret = $google_authenticator->createSecret();
        $code = $google_authenticator->getCode($secret);

        $this->user->update([
            'tfa_type' => TFAType::APP,
            'tfa_secret' => $secret,
        ]);

        $this->actingAs($this->user)->json('POST', '/auth/2fa/confirm', [
            'code' => $code,
        ])->assertOk();

        Event::assertDispatched(TfaRecoveryCodesChanged::class);

        return [$this->user, new TfaRecoveryCodesChanged($this->user)];
    }

    public function testConfirmAppTfaNoPreferences(): void
    {
        Notification::fake();
        Event::fake([TfaRecoveryCodesChanged::class]);

        $google_authenticator = new PHPGangsta_GoogleAuthenticator();

        $secret = $google_authenticator->createSecret();
        $code = $google_authenticator->getCode($secret);

        $this->user->update([
            'tfa_type' => TFAType::APP,
            'tfa_secret' => $secret,
        ]);

        $this->actingAs($this->user)->json('POST', '/auth/2fa/confirm', [
            'code' => $code,
        ])->assertOk()->assertJsonStructure([
            'data' => [
                'recovery_codes',
            ],
        ]);

        Notification::assertNotSentTo(
            [$this->user],
            TFARecoveryCodes::class,
        );
        Event::assertNotDispatched(TfaRecoveryCodesChanged::class);

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
            TFAInitialization::class,
        );

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'tfa_type' => TFAType::EMAIL,
            'tfa_secret' => null,
            'is_tfa_active' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'type',
                ],
            ]);
    }

    public function testSetupEmailTfaWithWebhookEvent(): array
    {
        Event::fake([TfaInit::class]);
        Notification::fake();

        $response = $this->actingAs($this->user)->json('POST', '/auth/2fa/setup', [
            'type' => TFAType::EMAIL,
        ]);
        $response->assertOk();

        $code = OneTimeSecurityCode::where('user_id', $this->user->getKey())->first();

        Event::assertDispatched(TfaInit::class);

        return [$this->user, $code->code, new TfaInit($this->user, $code->code)];
    }

    /**
     * @depends testSetupEmailTfaWithWebhookEvent
     */
    public function testSetupEmailTfaWithWebhookDispatch($payload): void
    {
        $webHook = WebHook::factory()->create([
            'events' => [
                'TfaInit',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        [$user, $code, $event] = $payload;

        $listener = new WebHookEventListener();
        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $user, $code) {
            $payload = $job->payload;

            $data = $this->decryptData($payload['data']);

            if (!$data) {
                return false;
            }

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $data['user']['id'] === $user->getKey()
                && $data['security_code'] === $code
                && $payload['data_type'] === 'TfaCode'
                && $payload['event'] === 'TfaInit'
                && $payload['issuer_type'] === IssuerType::USER;
        });
    }

    public function testConfirmEmailTfa(): void
    {
        $this->user->preferences()->update([
            'recovery_code_changed_alert' => true,
        ]);
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
        ])->assertOk()->assertJsonStructure([
            'data' => [
                'recovery_codes',
            ],
        ]);

        Notification::assertSentTo(
            [$this->user],
            TFARecoveryCodes::class,
        );

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'is_tfa_active' => true,
        ]);

        $this->assertDatabaseCount('one_time_security_codes', 3);
    }

    public function testConfirmEmailTfaWebhookEvent(): array
    {
        $this->user->preferences()->update([
            'recovery_code_changed_alert' => true,
        ]);
        Notification::fake();
        Event::fake([TfaRecoveryCodesChanged::class]);

        $code = $this->oneTimeSecurityCodeService
            ->generateOneTimeSecurityCode($this->user, Config::get('tfa.code_expires_time'));

        $this->user->update([
            'tfa_type' => TFAType::EMAIL,
        ]);

        $this->actingAs($this->user)->json('POST', '/auth/2fa/confirm', [
            'code' => $code,
        ])->assertOk();

        Event::assertDispatched(TfaRecoveryCodesChanged::class);

        return [$this->user, new TfaRecoveryCodesChanged($this->user)];
    }

    public function testConfirmEmailTfaNoPreferences(): void
    {
        Notification::fake();
        Event::fake([TfaRecoveryCodesChanged::class]);

        $code = $this->oneTimeSecurityCodeService->generateOneTimeSecurityCode(
            $this->user,
            Config::get('tfa.code_expires_time'),
        );

        $this->user->update([
            'tfa_type' => TFAType::EMAIL,
        ]);

        $this->actingAs($this->user)->json('POST', '/auth/2fa/confirm', [
            'code' => $code,
        ])->assertOk()->assertJsonStructure([
            'data' => [
                'recovery_codes',
            ],
        ]);

        Notification::assertNotSentTo(
            [$this->user],
            TFARecoveryCodes::class,
        );
        Event::assertNotDispatched(TfaRecoveryCodesChanged::class);

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
                'key' => Exceptions::getKey(Exceptions::CLIENT_INVALID_PASSWORD),
            ]);
    }

    public function testRecoveryCodesCreateNoTfa(): void
    {
        $this->actingAs($this->user)->json('POST', '/auth/2fa/recovery/create', [
            'password' => $this->password,
        ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'key' => Exceptions::coerce(Exceptions::CLIENT_TFA_NOT_SET_UP)->key,
            ]);
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testRecoveryCodesCreate($method, $secret): void
    {
        $this->user->preferences()->update([
            'recovery_code_changed_alert' => true,
        ]);
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
            TFARecoveryCodes::class,
        );

        $recovery_codes = OneTimeSecurityCode::where('user_id', '=', $this->user->getKey())
            ->whereNull('expires_at')
            ->get();

        $response->assertJsonStructure([
            'data' => [
                'recovery_codes',
            ],
        ]);

        $this->assertDatabaseCount('one_time_security_codes', count($recovery_codes));
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testRecoveryCodesCreateNoPreferences($method, $secret): void
    {
        Notification::fake();
        Event::fake([TfaRecoveryCodesChanged::class]);

        $this->user->update([
            'tfa_type' => $method,
            'tfa_secret' => $secret,
            'is_tfa_active' => true,
        ]);

        $response = $this->actingAs($this->user)->json('POST', '/auth/2fa/recovery/create', [
            'password' => $this->password,
        ])->assertOk();

        Notification::assertNotSentTo(
            [$this->user],
            TFARecoveryCodes::class,
        );
        Event::assertNotDispatched(TfaRecoveryCodesChanged::class);

        $recovery_codes = OneTimeSecurityCode::where('user_id', '=', $this->user->getKey())
            ->whereNull('expires_at')
            ->get();

        $response->assertJsonStructure([
            'data' => [
                'recovery_codes',
            ],
        ]);

        $this->assertDatabaseCount('one_time_security_codes', count($recovery_codes));
    }

    public function testRecoveryCodesCreateWebhookEvent(): array
    {
        $this->user->preferences()->update([
            'recovery_code_changed_alert' => true,
        ]);
        Notification::fake();
        Event::fake([TfaRecoveryCodesChanged::class]);

        $this->user->update([
            'tfa_type' => TFAType::APP,
            'tfa_secret' => 'secret',
            'is_tfa_active' => true,
        ]);

        $this->actingAs($this->user)->json('POST', '/auth/2fa/recovery/create', [
            'password' => $this->password,
        ])->assertOk();

        Event::assertDispatched(TfaRecoveryCodesChanged::class);

        return [$this->user, new TfaRecoveryCodesChanged($this->user)];
    }

    /**
     * @depends testConfirmAppTfaWebhookEvent
     * @depends testConfirmEmailTfaWebhookEvent
     * @depends testRecoveryCodesCreateWebhookEvent
     */
    public function testConfirmTfaWebhookDispatch($payload1, $payload2, $payload3): void
    {
        $webHook = WebHook::factory()->create([
            'events' => [
                'TfaRecoveryCodesChanged',
            ],
            'model_type' => $this->user::class,
            'creator_id' => $this->user->getKey(),
            'with_issuer' => true,
            'with_hidden' => false,
        ]);

        Bus::fake();

        [$user1, $event1] = $payload1;
        [$user2, $event2] = $payload2;
        [$user3, $event3] = $payload3;

        $listener = new WebHookEventListener();

        $listener->handle($event1);
        $listener->handle($event2);
        $listener->handle($event3);

        // Webhook after app TFA is confirmed
        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $user1) {
            $payload = $job->payload;

            $data = $this->decryptData($payload['data']);

            if (!$data) {
                return false;
            }

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $data['id'] === $user1->getKey()
                && $payload['data_type'] === 'User'
                && $payload['event'] === 'TfaRecoveryCodesChanged'
                && $payload['issuer_type'] === IssuerType::USER;
        });

        // Webhook after email TFA is confirmed
        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $user2) {
            $payload = $job->payload;

            $data = $this->decryptData($payload['data']);

            if (!$data) {
                return false;
            }

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $data['id'] === $user2->getKey()
                && $payload['data_type'] === 'User'
                && $payload['event'] === 'TfaRecoveryCodesChanged'
                && $payload['issuer_type'] === IssuerType::USER;
        });

        // Webhook after recovery codes are created
        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $user3) {
            $payload = $job->payload;

            $data = $this->decryptData($payload['data']);

            if (!$data) {
                return false;
            }

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $data['id'] === $user3->getKey()
                && $payload['data_type'] === 'User'
                && $payload['event'] === 'TfaRecoveryCodesChanged'
                && $payload['issuer_type'] === IssuerType::USER;
        });
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
                'key' => Exceptions::coerce(Exceptions::CLIENT_TFA_NOT_SET_UP)->key,
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
                'key' => Exceptions::coerce(Exceptions::CLIENT_TFA_NOT_SET_UP)->key,
            ]);
    }

    public function testRemoveUserTfaYourself(): void
    {
        $this->user->givePermissionTo('users.2fa_remove');

        $this->actingAs($this->user)->json('POST', '/users/id:' . $this->user->getKey() . '/2fa/remove')
            ->assertStatus(422)
            ->assertJsonFragment([
                'key' => Exceptions::coerce(Exceptions::CLIENT_TFA_CANNOT_REMOVE)->key,
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
                    'preferences',
                    'created_at',
                ],
            ])
            ->assertJsonFragment([
                'name' => 'Registered user',
                'email' => $email,
            ])
            ->assertJsonMissing(['name' => 'Authenticated'])
            ->assertJsonFragment([
                'successful_login_attempt_alert' => false,
                'failed_login_attempt_alert' => true,
                'new_localization_login_alert' => true,
                'recovery_code_changed_alert' => true,
            ]);

        $user = User::where('email', $email)->first();

        $this->assertDatabaseHas('user_preferences', [
            'id' => $user->preferences_id,
            'successful_login_attempt_alert' => false,
            'failed_login_attempt_alert' => true,
            'new_localization_login_alert' => true,
            'recovery_code_changed_alert' => true,
        ]);

        Notification::assertSentTo(
            [$user],
            UserRegistered::class,
        );
    }

    public function testRegisterWithUnassignableRoles(): void
    {
        /** @var Role $role */
        $role = Role::query()
            ->where('type', RoleType::UNAUTHENTICATED)
            ->firstOrFail();

        $role->givePermissionTo('auth.register');

        Role::query()
            ->where('type', RoleType::AUTHENTICATED)
            ->firstOrFail();

        $newRole = Role::factory()->create([
            'is_registration_role' => false,
        ]);

        $email = $this->faker->email();
        $username = 'Registered user';
        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $email,
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
            'roles' => [
                $newRole->getKey(),
            ],
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseMissing('users', [
            'name' => $username,
            'email' => $email,
        ]);
    }

    public function testRegisterWithRoles(): void
    {
        Notification::fake();

        /** @var Role $role */
        $role = Role::query()
            ->where('type', RoleType::UNAUTHENTICATED)
            ->firstOrFail();

        $role->givePermissionTo('auth.register');

        $authenticated = Role::query()
            ->where('type', RoleType::AUTHENTICATED)
            ->firstOrFail();

        /** @var Role $newRole */
        $newRole = Role::factory()->create([
            'is_registration_role' => true,
        ]);

        $email = $this->faker->email();
        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $email,
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
            'roles' => [
                $newRole->getKey(),
            ],
        ])
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJsonFragment([
                $newRole->getKeyName() => $newRole->getKey(),
                'name' => $newRole->name,
            ]);

        /** @var User $user */
        $user = User::query()->where('email', $email)->first();

        $this->assertTrue(
            $user->hasAllRoles([$newRole, $authenticated]),
        );

        Notification::assertSentTo(
            [$user],
            UserRegistered::class,
        );
    }

    public function testRegisterWithPhone(): void
    {
        Notification::fake();

        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $email = $this->faker->email();
        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $email,
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
            'phone' => '+48123456789',
            'birthday_date' => '1990-01-01',
        ])
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'avatar',
                    'roles',
                    'preferences',
                    'birthday_date',
                    'phone',
                    'phone_country',
                    'phone_number',
                ],
            ])
            ->assertJsonFragment([
                'name' => 'Registered user',
                'email' => $email,
                'birthday_date' => '1990-01-01',
                'phone' => '+48123456789',
                'phone_country' => 'PL',
                'phone_number' => '12 345 67 89',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'phone_country' => 'PL',
            'phone_number' => '12 345 67 89',
            'birthday_date' => '1990-01-01',
        ]);
    }

    public function testRegisterWithMetadata(): void
    {
        Notification::fake();

        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $email = $this->faker->email();
        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $email,
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
            'metadata' => [
                'meta' => 'value',
            ],
            'metadata_private' => [
                'meta_priv' => 'test',
            ],
            'metadata_personal' => [
                'meta_personal' => 'test2',
            ],
        ])
            ->assertCreated()
            ->assertJsonFragment([
                'metadata_personal' => [
                    'meta_personal' => 'test2',
                ],
            ])
            ->assertJsonMissing([
                'metadata' => [
                    'meta' => 'value',
                ],
            ])->assertJsonMissing([
                'metadata_private' => [
                    'meta_priv' => 'test',
                ],
            ]);

        $user = User::where('email', $email)->first();

        $this->assertDatabaseMissing('metadata', [
            'model_id' => $user->getKey(),
            'name' => 'meta',
            'value' => 'value',
        ]);

        $this->assertDatabaseMissing('metadata', [
            'model_id' => $user->getKey(),
            'name' => 'meta_priv',
            'value' => 'test',
        ]);

        $this->assertDatabaseHas('metadata_personals', [
            'model_id' => $user->getKey(),
            'name' => 'meta_personal',
            'value' => 'test2',
        ]);

        Notification::assertSentTo(
            [$user],
            UserRegistered::class,
        );
    }

    private function decryptData(string $data): array|false
    {
        $decoded = base64_decode($data);
        $ivLen = openssl_cipher_iv_length($this->cipher);

        if ($ivLen === false) {
            return false;
        }

        $iv = mb_substr($decoded, 0, $ivLen, '8bit');
        $ciphertext = mb_substr($decoded, $ivLen, null, '8bit');
        $decrypted = openssl_decrypt($ciphertext, $this->cipher, $this->webhookKey, OPENSSL_RAW_DATA, $iv);

        if ($decrypted) {
            return json_decode($decrypted, true);
        }

        return false;
    }
}
