<?php

namespace Tests\Feature;

use App\Enums\RoleType;
use App\Models\Permission;
use App\Models\Role;
use Tests\TestCase;

class RoleTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Role::query()->delete();
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/roles');

        $response->assertForbidden();
    }

    public function testIndex(): void
    {
        $this->user->givePermissionTo('roles.show');

        $role1 = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $role2 = Role::create([
            'name' => 'role2',
            'description' => 'Role 2',
        ]);

        $response = $this->actingAs($this->user)->getJson('/roles');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([[
                $role1->getKeyName() => $role1->getKey(),
                'name' => $role1->name,
                'description' => $role1->description,
                'assignable' => true,
                'deletable' => true,
            ]])
            ->assertJsonFragment([[
                $role2->getKeyName() => $role2->getKey(),
                'name' => $role2->name,
                'description' => $role2->description,
                'assignable' => true,
                'deletable' => true,
            ]]);
    }

    public function testIndexSearchByName(): void
    {
        $this->user->givePermissionTo('roles.show');

        $role1 = Role::create([
            'name' => 'alpha.1',
            'description' => 'Alpha 1',
        ]);

        Role::create([
            'name' => 'beta.1',
            'description' => 'Beta 1',
        ]);

        $role2 = Role::create([
            'name' => 'custom.alpha',
            'description' => 'Custom alpha',
        ]);

        Role::create([
            'name' => 'gamma.1',
            'description' => 'Gamma 1',
        ]);

        $response = $this->actingAs($this->user)->getJson('/roles?name=alpha');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([[
                $role1->getKeyName() => $role1->getKey(),
                'name' => $role1->name,
                'description' => $role1->description,
                'assignable' => true,
                'deletable' => true,
            ]])
            ->assertJsonFragment([[
                $role2->getKeyName() => $role2->getKey(),
                'name' => $role2->name,
                'description' => $role2->description,
                'assignable' => true,
                'deletable' => true,
            ]]);
    }

    public function testIndexSearchByDescription(): void
    {
        $this->user->givePermissionTo('roles.show');

        $role1 = Role::create([
            'name' => 'alpha.1',
            'description' => 'Alpha 1',
        ]);

        Role::create([
            'name' => 'beta.1',
            'description' => 'Beta 1',
        ]);

        $role2 = Role::create([
            'name' => 'custom.alpha',
            'description' => 'Custom alpha',
        ]);

        Role::create([
            'name' => 'gamma.1',
            'description' => 'Gamma 1',
        ]);

        $response = $this->actingAs($this->user)->getJson('/roles?description=alpha');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([[
                $role1->getKeyName() => $role1->getKey(),
                'name' => $role1->name,
                'description' => $role1->description,
                'assignable' => true,
                'deletable' => true,
            ]])
            ->assertJsonFragment([[
                $role2->getKeyName() => $role2->getKey(),
                'name' => $role2->name,
                'description' => $role2->description,
                'assignable' => true,
                'deletable' => true,
            ]]);
    }

    public function testIndexSearchByAssignable(): void
    {
        $this->user->givePermissionTo('roles.show');

        $roleNoPermissions = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $roleHasPermissions = Role::create([
            'name' => 'role2',
            'description' => 'Role 2',
        ]);
        $roleHasPermissions->givePermissionTo('roles.show');

        $roleHasSomePermissions = Role::create([
            'name' => 'role3',
            'description' => 'Role 3',
        ]);
        $roleHasSomePermissions->givePermissionTo(['roles.show', 'roles.add']);

        $roleHasNoPermissions = Role::create([
            'name' => 'role4',
            'description' => 'Role 4',
        ]);
        $roleHasNoPermissions->givePermissionTo('roles.add');

        $response = $this->actingAs($this->user)->getJson('/roles?assignable=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([[
                $roleNoPermissions->getKeyName() => $roleNoPermissions->getKey(),
                'name' => $roleNoPermissions->name,
                'description' => $roleNoPermissions->description,
                'assignable' => true,
                'deletable' => true,
            ]])
            ->assertJsonFragment([[
                $roleHasPermissions->getKeyName() => $roleHasPermissions->getKey(),
                'name' => $roleHasPermissions->name,
                'description' => $roleHasPermissions->description,
                'assignable' => true,
                'deletable' => true,
            ]]);
    }

    public function testIndexSearchByUnassignable(): void
    {
        $this->user->givePermissionTo('roles.show');

        Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $roleHasPermissions = Role::create([
            'name' => 'role2',
            'description' => 'Role 2',
        ]);
        $roleHasPermissions->givePermissionTo('roles.show');

        $roleHasSomePermissions = Role::create([
            'name' => 'role3',
            'description' => 'Role 3',
        ]);
        $roleHasSomePermissions->givePermissionTo(['roles.show', 'roles.add']);

        $roleHasNoPermissions = Role::create([
            'name' => 'role4',
            'description' => 'Role 4',
        ]);
        $roleHasNoPermissions->givePermissionTo('roles.add');

        $response = $this->actingAs($this->user)->getJson('/roles?assignable=0');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([[
                $roleHasSomePermissions->getKeyName() => $roleHasSomePermissions->getKey(),
                'name' => $roleHasSomePermissions->name,
                'description' => $roleHasSomePermissions->description,
                'assignable' => false,
                'deletable' => true,
            ]])
            ->assertJsonFragment([[
                $roleHasNoPermissions->getKeyName() => $roleHasNoPermissions->getKey(),
                'name' => $roleHasNoPermissions->name,
                'description' => $roleHasNoPermissions->description,
                'assignable' => false,
                'deletable' => true,
            ]]);
    }

    public function testIndexSearch(): void
    {
        $this->user->givePermissionTo('roles.show');

        $role1 = Role::create([
            'name' => 'name.yes',
            'description' => 'Name 1',
        ]);

        Role::create([
            'name' => 'name.no',
            'description' => 'Name 2',
        ]);

        $role2 = Role::create([
            'name' => 'description.1',
            'description' => 'Description Yes',
        ]);

        Role::create([
            'name' => 'description.2',
            'description' => 'Description no',
        ]);

        $response = $this->actingAs($this->user)->getJson('/roles?search=yes');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([[
                $role1->getKeyName() => $role1->getKey(),
                'name' => $role1->name,
                'description' => $role1->description,
                'assignable' => true,
                'deletable' => true,
            ]])
            ->assertJsonFragment([[
                $role2->getKeyName() => $role2->getKey(),
                'name' => $role2->name,
                'description' => $role2->description,
                'assignable' => true,
                'deletable' => true,
            ]]);
    }

    public function testShowUnauthorized(): void
    {
        $role = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $response = $this->getJson('/roles/id:' . $role->getKey());

        $response->assertForbidden();
    }

    public function testShow(): void
    {
        $this->user->givePermissionTo('roles.show_details');

        $role = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $response = $this->actingAs($this->user)->getJson('/roles/id:' . $role->getKey());

        $response->assertOk()
            ->assertJson(['data' => [
                $role->getKeyName() => $role->getKey(),
                'name' => $role->name,
                'description' => $role->description,
                'assignable' => true,
                'deletable' => true,
                'permissions' => [],
            ]]);
    }

    public function testShowPermissions(): void
    {
        $this->user->givePermissionTo('roles.show_details');

        $role = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $permission1 = Permission::create(['name' => 'test.custom1']);
        $permission2 = Permission::create(['name' => 'test.custom2']);
        $role->syncPermissions([$permission1, $permission2]);
        $this->user->givePermissionTo([$permission1]);

        $response = $this->actingAs($this->user)->getJson('/roles/id:' . $role->getKey());

        $response->assertOk()
            ->assertJson(['data' => [
                $role->getKeyName() => $role->getKey(),
                'name' => $role->name,
                'description' => $role->description,
                'assignable' => false,
                'deletable' => true,
                'permissions' => [
                    'test.custom1',
                    'test.custom2',
                ],
            ]]);
    }

    public function testShowPermissionsAssignable(): void
    {
        $this->user->givePermissionTo('roles.show_details');

        $role = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $permission1 = Permission::create(['name' => 'test.custom1']);
        $permission2 = Permission::create(['name' => 'test.custom2']);
        $role->syncPermissions([$permission1, $permission2]);
        $this->user->givePermissionTo([$permission1, $permission2]);

        $response = $this->actingAs($this->user)->getJson('/roles/id:' . $role->getKey());

        $response->assertOk()
            ->assertJson(['data' => [
                $role->getKeyName() => $role->getKey(),
                'name' => $role->name,
                'description' => $role->description,
                'assignable' => true,
                'deletable' => true,
                'permissions' => [
                    'test.custom1',
                    'test.custom2',
                ],
            ]]);
    }

    public function testCreateUnauthorized(): void
    {
        Permission::create(['name' => 'test.custom1']);
        Permission::create(['name' => 'test.custom2']);

        $response = $this->postJson('/roles', [
            'name' => 'test_role',
            'description' => 'Test role',
            'permissions' => [
                'test.custom1',
                'test.custom2',
            ],
        ]);

        $response->assertForbidden();
    }

    public function testCreateMissingPermissions(): void
    {
        $this->user->givePermissionTo('roles.add');

        Permission::create(['name' => 'test.custom1']);
        Permission::create(['name' => 'test.custom2']);

        $response = $this->actingAs($this->user)->postJson('/roles', [
            'name' => 'test_role',
            'description' => 'Test role',
            'permissions' => [
                'test.custom1',
                'test.custom2',
            ],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('roles', [
            'name' => 'test_role',
        ]);
    }

    public function testCreate(): void
    {
        $this->user->givePermissionTo('roles.add');

        $permission1 = Permission::create(['name' => 'test.custom1']);
        $permission2 = Permission::create(['name' => 'test.custom2']);
        $this->user->givePermissionTo([$permission1, $permission2]);

        $response = $this->actingAs($this->user)->postJson('/roles', [
            'name' => 'test_role',
            'description' => 'Test role',
            'permissions' => [
                'test.custom1',
                'test.custom2',
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'name' => 'test_role',
                'description' => 'Test role',
                'assignable' => true,
                'deletable' => true,
                'permissions' => [
                    'test.custom1',
                    'test.custom2',
                ],
            ]]);

        $this->assertDatabaseHas('roles', [
            'name' => 'test_role',
            'description' => 'Test role',
        ]);

        $role = Role::findByName('test_role');

        $this->assertTrue($role->hasPermissionTo('test.custom1'));
        $this->assertTrue($role->hasPermissionTo('test.custom2'));
    }

    public function testCreateWithoutDescription(): void
    {
        $this->user->givePermissionTo('roles.add');

        $this->actingAs($this->user)->postJson('/roles', [
                'name' => 'test_role',
            ])
            ->assertCreated()
            ->assertJson(['data' => [
                'name' => 'test_role',
                'description' => null,
                'assignable' => true,
                'deletable' => true,
                'permissions' => [],
            ]]);

        $this->assertDatabaseHas('roles', [
            'name' => 'test_role',
            'description' => null,
        ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $role = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $permission1 = Permission::create(['name' => 'test.custom1']);
        $permission2 = Permission::create(['name' => 'test.custom2']);
        Permission::create(['name' => 'test.custom3']);
        $role->syncPermissions([$permission1, $permission2]);

        $response = $this->patchJson('/roles/id:' . $role->getKey(), [
            'name' => 'test_role',
            'description' => 'Test role',
            'permissions' => [
                'test.custom2',
                'test.custom3',
            ],
        ]);

        $response->assertForbidden();
    }

    public function testUpdateMissingPermissions(): void
    {
        $this->user->givePermissionTo('roles.edit');

        $role = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $permission1 = Permission::create(['name' => 'test.custom1']);
        $permission2 = Permission::create(['name' => 'test.custom2']);
        $permission3 = Permission::create(['name' => 'test.custom3']);
        $role->syncPermissions([$permission1, $permission2]);
        $this->user->givePermissionTo([$permission2, $permission3]);

        $response = $this->actingAs($this->user)->patchJson('/roles/id:' . $role->getKey(), [
            'name' => 'test_role',
            'description' => 'Test role',
            'permissions' => [
                'test.custom2',
                'test.custom3',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertTrue($role->hasPermissionTo('test.custom1'));
        $this->assertTrue($role->hasPermissionTo('test.custom2'));
    }

    public function testUpdateWithMissingPermissions(): void
    {
        $this->user->givePermissionTo('roles.edit');

        $role = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $permission1 = Permission::create(['name' => 'test.custom1']);
        $permission2 = Permission::create(['name' => 'test.custom2']);
        Permission::create(['name' => 'test.custom3']);
        $role->syncPermissions([$permission1, $permission2]);
        $this->user->givePermissionTo([$permission1, $permission2]);

        $response = $this->actingAs($this->user)->patchJson('/roles/id:' . $role->getKey(), [
            'name' => 'test_role',
            'description' => 'Test role',
            'permissions' => [
                'test.custom2',
                'test.custom3',
            ],
        ]);

        $response->assertStatus(422);
        $this->assertTrue($role->hasPermissionTo('test.custom1'));
        $this->assertTrue($role->hasPermissionTo('test.custom2'));
    }

    public function testUpdateOwnerPermissions(): void
    {
        Permission::create(['name' => 'test.custom1']);

        $this->user->givePermissionTo(Permission::all());

        $owner = Role::create([
            'name' => 'owner',
            'description' => 'Owner',
        ]);
        $owner->type = RoleType::OWNER;
        $owner->save();
        $owner->givePermissionTo(Permission::all());

        $response = $this->actingAs($this->user)->patchJson('/roles/id:' . $owner->getKey(), [
            'name' => 'Owner 2',
            'description' => 'Owner 2',
            'permissions' => [
                'test.custom1',
            ],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('roles', [
            $owner->getKeyName() => $owner->getKey(),
            'name' => 'owner',
            'description' => 'Owner',
        ]);

        $this->assertTrue($owner->hasAllPermissions(Permission::all()));
    }

    public function testUpdate(): void
    {
        $this->user->givePermissionTo('roles.edit');

        $role = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $permission1 = Permission::create(['name' => 'test.custom1']);
        $permission2 = Permission::create(['name' => 'test.custom2']);
        $permission3 = Permission::create(['name' => 'test.custom3']);
        $role->syncPermissions([$permission1, $permission2]);
        $this->user->givePermissionTo([$permission1, $permission2, $permission3]);

        $response = $this->actingAs($this->user)->patchJson('/roles/id:' . $role->getKey(), [
            'name' => 'test_role',
            'description' => 'Test role',
            'permissions' => [
                'test.custom2',
                'test.custom3',
            ],
        ]);

        $response->assertOk()
            ->assertJson(['data' => [
                'name' => 'test_role',
                'description' => 'Test role',
                'assignable' => true,
                'deletable' => true,
                'permissions' => [
                    'test.custom2',
                    'test.custom3',
                ],
            ]]);

        $this->assertDatabaseHas('roles', [
            $role->getKeyName() => $role->getKey(),
            'name' => 'test_role',
            'description' => 'Test role',
        ]);

        $role = Role::findByName('test_role');

        $this->assertTrue($role->hasPermissionTo('test.custom2'));
        $this->assertTrue($role->hasPermissionTo('test.custom3'));
    }

    public function testDeleteUnauthorized(): void
    {
        $role = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $response = $this->deleteJson('/roles/id:' . $role->getKey());
        $response->assertForbidden();
    }

    public function testDelete(): void
    {
        $this->user->givePermissionTo('roles.remove');

        $role = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $response = $this->actingAs($this->user)->deleteJson('/roles/id:' . $role->getKey());
        $response->assertNoContent();

        $this->assertDatabaseMissing('roles', [
            $role->getKeyName() => $role->getKey(),
        ]);
    }

    public function testDeleteMissingPermissions(): void
    {
        $this->user->givePermissionTo('roles.remove');

        $role = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $permission1 = Permission::create(['name' => 'test.custom1']);
        $permission2 = Permission::create(['name' => 'test.custom2']);
        $role->syncPermissions([$permission1, $permission2]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/roles/id:' . $role->getKey());
        $response->assertStatus(422);

        $this->assertDatabaseHas('roles', [
            $role->getKeyName() => $role->getKey(),
        ]);

        $this->assertDatabaseHas('role_has_permissions', [
            'role_id' => $role->getKey(),
            'permission_id' => $permission1->getKey(),
        ]);

        $this->assertDatabaseHas('role_has_permissions', [
            'role_id' => $role->getKey(),
            'permission_id' => $permission2->getKey(),
        ]);
    }

    public function testDeleteOwnedPermissions(): void
    {
        $this->user->givePermissionTo('roles.remove');

        $role = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $permission1 = Permission::create(['name' => 'test.custom1']);
        $permission2 = Permission::create(['name' => 'test.custom2']);
        $role->syncPermissions([$permission1, $permission2]);
        $this->user->givePermissionTo([$permission1, $permission2]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/roles/id:' . $role->getKey());
        $response->assertNoContent();

        $this->assertDatabaseMissing('roles', [
            $role->getKeyName() => $role->getKey(),
        ]);

        $this->assertDatabaseMissing('role_has_permissions', [
            'role_id' => $role->getKey(),
            'permission_id' => $permission1->getKey(),
        ]);

        $this->assertDatabaseMissing('role_has_permissions', [
            'role_id' => $role->getKey(),
            'permission_id' => $permission2->getKey(),
        ]);
    }

    public function testDeleteOwnerRole(): void
    {
        $this->user->givePermissionTo(Permission::all());

        $owner = Role::create([
            'name' => 'Owner',
            'description' => 'Owner',
        ]);
        $owner->type = RoleType::OWNER;
        $owner->save();

        $response = $this->actingAs($this->user)
            ->deleteJson('/roles/id:' . $owner->getKey());
        $response->assertStatus(422);

        $this->assertDatabaseHas('roles', [
            $owner->getKeyName() => $owner->getKey(),
        ]);
    }

    public function testDeleteUnauthenticatedRole(): void
    {
        $this->user->givePermissionTo(Permission::all());

        $owner = Role::create([
            'name' => 'Unauthenticated',
            'description' => 'Unauthenticated',
        ]);
        $owner->type = RoleType::UNAUTHENTICATED;
        $owner->save();

        $response = $this->actingAs($this->user)
            ->deleteJson('/roles/id:' . $owner->getKey());
        $response->assertStatus(422);

        $this->assertDatabaseHas('roles', [
            $owner->getKeyName() => $owner->getKey(),
        ]);
    }
}
