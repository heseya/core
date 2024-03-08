<?php

namespace Tests\Feature\Auth;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\IssuerType;
use App\Enums\TFAType;
use App\Events\TfaInit;
use App\Events\TfaRecoveryCodesChanged;
use App\Listeners\WebHookEventListener;
use App\Models\OneTimeSecurityCode;
use App\Models\User;
use App\Models\WebHook;
use App\Notifications\TFAInitialization;
use App\Notifications\TFARecoveryCodes;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPGangsta_GoogleAuthenticator;
use Spatie\WebhookServer\CallWebhookJob;

class AuthTfaTest extends AuthTestCase
{
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
                'key' => Exceptions::CLIENT_TFA_ALREADY_SET_UP->name,
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
            function (TFAInitialization $notification) {
                $mail = $notification->toMail($this->user);
                $this->assertEquals('Potwierdzenie 2FA', $mail->subject);

                return true;
            }
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

    public function testSetupEmailTfaDifferentLanguage(): void
    {
        Notification::fake();

        $this->actingAs($this->user)
            ->json('POST', '/auth/2fa/setup', ['type' => TFAType::EMAIL,], ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'type',
                ],
            ]);

        Notification::assertSentTo(
            [$this->user],
            TFAInitialization::class,
            function (TFAInitialization $notification) {
                $mail = $notification->toMail($this->user);
                $this->assertEquals('2FA confirmation', $mail->subject);

                return true;
            }
        );

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'tfa_type' => TFAType::EMAIL,
            'tfa_secret' => null,
            'is_tfa_active' => false,
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
                && IssuerType::USER->is($payload['issuer_type']);
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
            function (TFARecoveryCodes $notification) {
                $mail = $notification->toMail($this->user);
                $this->assertEquals('Kody odzyskiwania 2FA', $mail->subject);

                return true;
            }
        );

        $this->assertDatabaseHas('users', [
            'id' => $this->user->getKey(),
            'is_tfa_active' => true,
        ]);

        $this->assertDatabaseCount('one_time_security_codes', 3);
    }

    public function testConfirmEmailTfaDifferentLanguage(): void
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

        $this->actingAs($this->user)
            ->json('POST', '/auth/2fa/confirm', ['code' => $code,], ['Accept-Language' => 'en'])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'recovery_codes',
                ],
            ]);

        Notification::assertSentTo(
            [$this->user],
            TFARecoveryCodes::class,
            function (TFARecoveryCodes $notification) {
                $mail = $notification->toMail($this->user);
                $this->assertEquals('2FA recovery codes', $mail->subject);

                return true;
            }
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
                'key' => Exceptions::CLIENT_INVALID_PASSWORD->name,
            ]);
    }

    public function testRecoveryCodesCreateNoTfa(): void
    {
        $this->actingAs($this->user)->json('POST', '/auth/2fa/recovery/create', [
            'password' => $this->password,
        ])
            ->assertStatus(422)
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_TFA_NOT_SET_UP->name,
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
                && IssuerType::USER->is($payload['issuer_type']);
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
                && IssuerType::USER->is($payload['issuer_type']);
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
                && IssuerType::USER->is($payload['issuer_type']);
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
                'key' => Exceptions::CLIENT_TFA_NOT_SET_UP->name,
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
                'key' => Exceptions::CLIENT_TFA_NOT_SET_UP->name,
            ]);
    }

    public function testRemoveUserTfaYourself(): void
    {
        $this->user->givePermissionTo('users.2fa_remove');

        $this->actingAs($this->user)->json('POST', '/users/id:' . $this->user->getKey() . '/2fa/remove')
            ->assertStatus(422)
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_TFA_CANNOT_REMOVE->name,
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
}
