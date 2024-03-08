<?php

namespace Tests\Feature\Auth;

use App\Enums\TokenType;
use App\Models\App;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class AuthCheckIdentityTest extends TestCase
{
    public function testCheckIdentityUnauthorized(): void
    {
        $user = User::factory()->create();

        $token = $this->tokenService->createToken(
            $user,
            TokenType::IDENTITY,
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
                TokenType::IDENTITY,
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
            TokenType::IDENTITY,
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
            TokenType::IDENTITY,
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
            TokenType::IDENTITY,
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
}
