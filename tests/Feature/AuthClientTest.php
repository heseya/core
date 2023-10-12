<?php

namespace Tests\Feature;

use App\Enums\RoleType;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use App\Notifications\ResetPassword;
use App\Notifications\UserRegistered;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthClientTest extends TestCase
{
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();

        Config::set('client.mails', true);

        $this->user->preferences()->associate(UserPreference::create([
            'failed_login_attempt_alert' => false,
            'new_localization_login_alert' => false,
            'recovery_code_changed_alert' => false,
        ]));
        $this->user->save();
    }

    public function testResetPassword(): void
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
            function (ResetPassword $notification) use ($user): bool {
                $mail = $notification->toMail($user);
                $this->assertEquals('mail.client.password-reset', $mail->view);
                $this->assertStringContainsString($user->name, (string) $mail->render());

                return true;
            },
        );

        $response->assertNoContent();
    }

    public function testRegisterAsPartner(): void
    {
        Notification::fake();

        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $email = $this->faker->email();
        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $email,
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
            'metadata_personal' => [
                'partner' => true,
            ],
            'email_verify_url' => 'http://localhost/email/verify',
        ])
            ->assertCreated()
            ->assertJsonFragment([
                'metadata_personal' => [
                    'partner' => true,
                ],
            ]);

        $user = User::where('email', $email)->first();

        $this->assertDatabaseHas('metadata_personals', [
            'model_id' => $user->getKey(),
            'name' => 'partner',
            'value' => true,
        ]);

        Notification::assertSentTo(
            [$user],
            UserRegistered::class,
            function (UserRegistered $notification) use ($user): bool {
                $mail = $notification->toMail($user);
                $this->assertEquals('mail.client.partner-register', $mail->view);
                $this->assertStringContainsString($user->name, (string) $mail->render());

                return true;
            },
        );
    }

    public function testRegisterAsUser(): void
    {
        Notification::fake();

        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $email = $this->faker->email();
        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $email,
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
            'metadata_personal' => [
                'partner' => false,
            ],
            'email_verify_url' => 'http://localhost/email/verify',
        ])
            ->assertCreated()
            ->assertJsonFragment([
                'metadata_personal' => [
                    'partner' => false,
                ],
            ]);

        $user = User::where('email', $email)->first();

        $this->assertDatabaseHas('metadata_personals', [
            'model_id' => $user->getKey(),
            'name' => 'partner',
            'value' => false,
        ]);

        Notification::assertSentTo(
            [$user],
            UserRegistered::class,
            function (UserRegistered $notification) use ($user): bool {
                $mail = $notification->toMail($user);
                $this->assertEquals('mail.client.user-register', $mail->view);
                $this->assertStringContainsString($user->name, (string) $mail->render());

                return true;
            },
        );
    }
}
