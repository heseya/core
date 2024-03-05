<?php

namespace Tests\Feature;

use App\Enums\RoleType;
use App\Mail\ResetPassword;
use App\Mail\UserRegistered;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

        Mail::fake();

        $this->actingAs($this->user)->postJson('/users/reset-password', [
            'email' => $user->email,
            'redirect_url' => 'https://test.com',
        ])->assertNoContent();

        Mail::assertSent(ResetPassword::class, function (ResetPassword $mail) use ($user): bool {
            $mail->assertTo($user->email);
            // $mail->assertSeeInHtml($user->name);

            return true;
        });
    }

    public function testRegisterAsPartner(): void
    {
        Mail::fake();

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
        ])
            ->assertCreated()
            ->assertJsonFragment([
                'metadata_personal' => [
                    'partner' => true,
                ],
            ]);

        /** @var User $user */
        $user = User::where('email', $email)->first();

        $this->assertDatabaseHas('metadata_personals', [
            'model_id' => $user->getKey(),
            'name' => 'partner',
            'value' => true,
        ]);

        Mail::assertSent(UserRegistered::class, function (UserRegistered $mail) use ($user) {
            $mail->assertTo($user->email);
            $mail->assertHasSubject('Partner account registered');
            $this->assertEquals('mail.client.partner-register', $mail->view);

            return true;
        });
    }

    public function testRegisterAsUser(): void
    {
        Mail::fake();

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
        ])
            ->assertCreated()
            ->assertJsonFragment([
                'metadata_personal' => [
                    'partner' => false,
                ],
            ]);

        /** @var User $user */
        $user = User::where('email', $email)->first();

        $this->assertDatabaseHas('metadata_personals', [
            'model_id' => $user->getKey(),
            'name' => 'partner',
            'value' => false,
        ]);

        Mail::assertSent(UserRegistered::class, function (UserRegistered $mail) use ($user) {
            $mail->assertTo($user->email);
            $mail->assertHasSubject('User account registered');
            $this->assertEquals('mail.client.user-register', $mail->view);

            return true;
        });
    }
}
