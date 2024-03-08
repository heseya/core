<?php

namespace Tests\Feature\Auth;

use App\Enums\IssuerType;
use App\Enums\RoleType;
use App\Events\PasswordReset;
use App\Listeners\WebHookEventListener;
use App\Models\Role;
use App\Models\User;
use App\Models\WebHook;
use App\Notifications\ResetPassword;
use Domain\Language\Language;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\WebhookServer\CallWebhookJob;
use Support\Enum\Status;

class AuthPasswordTest extends AuthTestCase
{
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

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use ($user) {
                $mail = $notification->toMail($user);
                $this->assertEquals('Wniosek o zmianę hasła', $mail->subject);

                return true;
            }
        );

        $response->assertNoContent();
    }

    public function testResetPasswordMailAcceptLanguage(): void
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

        $this->actingAs($this->user)
            ->json(
                'POST',
                '/users/reset-password',
                [
                    'email' => $user->email,
                    'redirect_url' => 'https://test.com',
                ],
                [
                    'Accept-Language' => 'en',
                    'X-Sales-Channel' => SalesChannel::query()->value('id'),
                ],
            )->assertNoContent();

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use ($user) {
                $mail = $notification->toMail($user);
                $this->assertEquals('Request for password change', $mail->subject);

                return true;
            }
        );
    }

    public function testResetPasswordMailSalesChannel(): void
    {
        $this->user->givePermissionTo('auth.password_reset');

        $email = $this->faker->unique()->safeEmail;
        $password = 'Passwd###111';

        $en = Language::firstOrCreate([
            'iso' => 'en',
        ], [
            'name' => 'English',
            'default' => false,
        ]);

        $salesChannel = SalesChannel::factory()->create(['status' => Status::ACTIVE, 'default_language_id' => $en->getKey()]);

        $user = User::factory()->create([
            'name' => $this->faker->firstName() . ' ' . $this->faker->lastName(),
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        Notification::fake();

        $this->actingAs($this->user)
            ->json(
                'POST',
                '/users/reset-password',
                [
                    'email' => $user->email,
                    'redirect_url' => 'https://test.com',
                ],
                [
                    'X-Sales-Channel' => $salesChannel->getKey(),
                ],
            )->assertNoContent();

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            function (ResetPassword $notification) use ($user) {
                $mail = $notification->toMail($user);
                $this->assertEquals('Request for password change', $mail->subject);

                return true;
            }
        );
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
                && IssuerType::USER->is($payload['issuer_type']);
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
        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.password_change');

        $this->putJson('/users/password', [
            'password' => 'test',
            'password_new' => 'Test1@3456',
        ])->assertForbidden();
    }

    public function testChangePassword(): void
    {
        /** @var User $user */
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
        /** @var User $user */
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
}
