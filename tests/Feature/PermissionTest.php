<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/roles');

        $response->assertForbidden();
    }

    public function testIndex(): void
    {
        $this->user->givePermissionTo('roles.show_details');

        $permissionCount = Permission::count();

        $permission1 = Permission::create([
            'name' => 'permission1',
            'description' => 'Permission 1',
        ]);

        $permission2 = Permission::create([
            'name' => 'permission2',
            'description' => 'Permission 2',
        ]);

        $permission3 = Permission::create([
            'name' => 'permission3',
            'description' => 'Permission 3',
        ]);

        $this->user->givePermissionTo($permission3);
        $response = $this->actingAs($this->user)->getJson('/permissions');

        $response->assertOk()
            ->assertJsonCount($permissionCount + 3, 'data')
            ->assertJsonFragment([[
                $permission1->getKeyName() => $permission1->getKey(),
                'name' => $permission1->name,
                'description' => $permission1->description,
                'assignable' => false,
            ]])
            ->assertJsonFragment([[
                $permission2->getKeyName() => $permission2->getKey(),
                'name' => $permission2->name,
                'description' => $permission2->description,
                'assignable' => false,
            ]])
            ->assertJsonFragment([[
                $permission3->getKeyName() => $permission3->getKey(),
                'name' => $permission3->name,
                'description' => $permission3->description,
                'assignable' => true,
            ]]);
    }

    public function testIndexAssignable(): void
    {
        $this->user->givePermissionTo('roles.show_details');

        $permission1 = Permission::create([
            'name' => 'permission1',
            'description' => 'Permission 1',
        ]);

        $permission2 = Permission::create([
            'name' => 'permission2',
            'description' => 'Permission 2',
        ]);

        $this->user->givePermissionTo($permission1);
        $response = $this->actingAs($this->user)->getJson('/permissions?assignable=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([[
                $permission1->getKeyName() => $permission1->getKey(),
                'name' => $permission1->name,
                'description' => $permission1->description,
                'assignable' => true,
            ]])
            ->assertJsonMissing([[
                $permission2->getKeyName() => $permission2->getKey(),
                'name' => $permission2->name,
                'description' => $permission2->description,
                'assignable' => false,
            ]])
            ->assertJsonMissing([
                'assignable' => false,
            ]);
    }

    public function testIndexUnassignable(): void
    {
        $this->user->givePermissionTo('roles.show_details');

        $permissionCount = Permission::count();

        $permission1 = Permission::create([
            'name' => 'permission1',
            'description' => 'Permission 1',
        ]);

        $permission2 = Permission::create([
            'name' => 'permission2',
            'description' => 'Permission 2',
        ]);

        $this->user->givePermissionTo($permission2);
        $response = $this->actingAs($this->user)->getJson('/permissions?assignable=0');

        $response->assertOk()
            ->assertJsonCount($permissionCount, 'data')
            ->assertJsonFragment([[
                $permission1->getKeyName() => $permission1->getKey(),
                'name' => $permission1->name,
                'description' => $permission1->description,
                'assignable' => false,
            ]])
            ->assertJsonMissing([[
                $permission2->getKeyName() => $permission2->getKey(),
                'name' => $permission2->name,
                'description' => $permission2->description,
                'assignable' => true,
            ]])
            ->assertJsonMissing([
                'assignable' => true,
            ]);
    }

    public function testIndexWithPermissionRolesShowDetails(): void
    {
        $this->user->givePermissionTo('roles.show_details');

        $this->actingAs($this->user)->getJson('/permissions')->assertOk();
    }

    public function testIndexWithPermissionRolesAdd(): void
    {
        $this->user->givePermissionTo('roles.add');

        $this->actingAs($this->user)->getJson('/permissions')->assertOk();
    }

    public function testIndexWithPermissionRolesEdit(): void
    {
        $this->user->givePermissionTo('roles.edit');

        $this->actingAs($this->user)->getJson('/permissions')->assertOk();
    }
}