<?php

namespace Tests\Feature\Auth;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Enums\ValidationError;
use App\Models\Role;
use App\Models\User;
use App\Notifications\UserRegistered;
use Domain\Language\Language;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Support\Facades\Notification;
use Support\Enum\Status;
use Symfony\Component\HttpFoundation\Response;

class AuthRegisterTest extends AuthTestCase
{
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
        ])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonFragment([
                'key' => ValidationError::EMAILUNIQUE->value,
                'message' => Exceptions::CLIENT_EMAIL_TAKEN->value,
            ]);

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
            function (UserRegistered $notification) use ($user) {
                $mail = $notification->toMail($user);
                $this->assertEquals('Witamy na pokładzie! Konto utworzono', $mail->subject);

                return true;
            }
        );
    }

    public function testRegisterMailWithAcceptLanguage(): void
    {
        Notification::fake();

        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $salesChannel = SalesChannel::query()->value('id');

        $email = $this->faker->email();
        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $email,
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
        ], [
            'Accept-Language' => 'en',
            'X-Sales-Channel' => $salesChannel,
        ])
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'Registered user',
                'email' => $email,
            ]);

        $user = User::where('email', $email)->first();

        Notification::assertSentTo(
            [$user],
            UserRegistered::class,
            function (UserRegistered $notification) use ($user) {
                $mail = $notification->toMail($user);
                $this->assertEquals('Welcome aboard! Account created', $mail->subject);

                return true;
            }
        );
    }

    public function testRegisterMailWithAcceptLanguageRegional(): void
    {
        Notification::fake();

        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $salesChannel = SalesChannel::query()->value('id');

        $email = $this->faker->email();
        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $email,
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
        ], [
            'Accept-Language' => 'en-EN',
            'X-Sales-Channel' => $salesChannel,
        ])
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'Registered user',
                'email' => $email,
            ]);

        $user = User::where('email', $email)->first();

        Notification::assertSentTo(
            [$user],
            UserRegistered::class,
            function (UserRegistered $notification) use ($user) {
                $mail = $notification->toMail($user);
                $this->assertEquals('Welcome aboard! Account created', $mail->subject);

                return true;
            }
        );
    }

    public function testRegisterMailWithSalesChannel(): void
    {
        Notification::fake();

        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('auth.register');

        $en = Language::firstOrCreate([
            'iso' => 'en',
        ], [
            'name' => 'English',
            'default' => false,
        ]);

        $salesChannel = SalesChannel::factory()->create(['status' => Status::ACTIVE, 'default_language_id' => $en->getKey()]);

        $email = $this->faker->email();
        $this->json('POST', '/register', [
            'name' => 'Registered user',
            'email' => $email,
            'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
        ], [
            'X-Sales-Channel' => $salesChannel->getKey(),
        ])
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'Registered user',
                'email' => $email,
            ]);

        $user = User::where('email', $email)->first();

        Notification::assertSentTo(
            [$user],
            UserRegistered::class,
            function (UserRegistered $notification) use ($user) {
                $mail = $notification->toMail($user);
                $this->assertEquals('Welcome aboard! Account created', $mail->subject);

                return true;
            }
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
        $this
            ->json('POST', '/register', [
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

        /** @var Role $role */
        $role = Role::query()->where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
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

        /** @var User $user */
        $user = User::query()->where('email', $email)->first();

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
}
