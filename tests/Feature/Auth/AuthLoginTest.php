<?php

namespace Tests\Feature\Auth;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\IssuerType;
use App\Enums\TFAType;
use App\Enums\TokenType;
use App\Events\FailedLoginAttempt;
use App\Events\NewLocalizationLoginAttempt;
use App\Events\SuccessfulLoginAttempt;
use App\Events\TfaSecurityCode as TfaSecurityCodeEvent;
use App\Listeners\WebHookEventListener;
use App\Models\OneTimeSecurityCode;
use App\Models\User;
use App\Models\UserLoginAttempt;
use App\Models\WebHook;
use App\Notifications\TFASecurityCode;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PHPGangsta_GoogleAuthenticator;
use Spatie\WebhookServer\CallWebhookJob;
use Symfony\Component\HttpFoundation\Response;

class AuthLoginTest extends AuthTestCase
{
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

    public function testSuccessfulLoginAttemptWithoutUserAgent(): array
    {
        $this->user->preferences()->update([
            'successful_login_attempt_alert' => true,
        ]);

        Event::fake([SuccessfulLoginAttempt::class]);

        $this
            ->actingAs($this->user)
            ->withHeaders([
                'User-Agent' => null,
            ])
            ->postJson('/login', [
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
                && IssuerType::USER->is($payload['issuer_type']);
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
                && IssuerType::USER->is($payload['issuer_type']);
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
                    && IssuerType::USER->is($payload['issuer_type']);
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
                && IssuerType::USER->is($payload['issuer_type']);
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
                'key' => Exceptions::CLIENT_TFA_NOT_SET_UP->name,
            ]);
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testLoginEnabledTfaNoCode(TFAType $method, ?string $secret): void
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
            Notification::assertSentTo(
                [$this->user],
                TFASecurityCode::class,
                function (TFASecurityCode $notification) {
                    $mail = $notification->toMail($this->user);
                    $this->assertEquals('Kod bezpieczeństwa 2FA', $mail->subject);

                    return true;
                }
            );
        }

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_TFA_REQUIRED->name,
            ]);
    }

    public function testLoginEnabledTfaNoCodeAcceptLanguage(): void
    {
        Notification::fake();

        $this->user->update([
            'tfa_type' => TFAType::EMAIL,
            'tfa_secret' => null,
            'is_tfa_active' => true,
        ]);

        $response = $this->actingAs($this->user)->json('POST', '/login', [
            'email' => $this->user->email,
            'password' => $this->password,
        ], ['Accept-Language' => 'en', 'X-Sales-Channel' => SalesChannel::query()->value('id')]);

        Notification::assertSentTo(
            [$this->user],
            TFASecurityCode::class,
            function (TFASecurityCode $notification) {
                $mail = $notification->toMail($this->user);
                $this->assertEquals('2FA security code', $mail->subject);

                return true;
            }
        );

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_TFA_REQUIRED->name,
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
                && IssuerType::USER->is($payload['issuer_type']);
        });
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testLoginEnabledTfaCode(TFAType $method, ?string $secret): void
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
                'key' => Exceptions::CLIENT_TFA_INVALID_TOKEN->name,
            ]);

        $this->assertDatabaseCount('one_time_security_codes', 1);
    }

    /**
     * @dataProvider tfaMethodProvider
     */
    public function testLoginEnabledTfaRecoveryCode(TFAType $method, ?string $secret): void
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
    public function testLoginEnabledTfaInvalidCode(TFAType $method, ?string $secret): void
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
                'key' => Exceptions::CLIENT_TFA_INVALID_TOKEN->name,
            ]);
    }

    public function testLogout(): void
    {
        $uuid = Str::uuid()->toString();
        $token = $this->tokenService->createToken(
            $this->user,
            TokenType::ACCESS,
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
}
