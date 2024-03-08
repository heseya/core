<?php

namespace Tests\Feature\Auth;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Models\App;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Tests\TestCase;

class AuthProfileTest extends TestCase
{
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
        $this->assertDatabaseCount('user_preferences', 1);
        $this->assertDatabaseHas('user_preferences', [
            'id' => $user->preferences_id,
            'successful_login_attempt_alert' => true,
            'failed_login_attempt_alert' => false,
            'new_localization_login_alert' => false,
            'recovery_code_changed_alert' => false,
        ]);
    }

    public function testUpdateProfileRemovePhone(): void
    {
        $user = User::factory()->create([
            'phone_country' => 'PL',
            'phone_number' => '12 345 67 89',
        ]);
        $user->preferences()->associate(UserPreference::create());
        $user->save();

        $this->actingAs($user)->json('PATCH', '/auth/profile', [
            'phone' => null,
        ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $user->getKey(),
                'email' => $user->email,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'phone' => null,
                'phone_country' => null,
                'phone_number' => null,
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'phone_number' => null,
            'phone_country' => null,
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
                'key' => Exceptions::CLIENT_JOINING_NON_JOINABLE_ROLE->name,
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
}
