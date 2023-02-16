<?php

namespace Tests\Feature;

use App\Models\Permission;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/roles');

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('roles.show_details');

        $permissionCount = Permission::count();

        $permission1 = Permission::create([
            'name' => 'permission1',
            'display_name' => 'Display name',
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

        $this->$user->givePermissionTo($permission3);
        $response = $this->actingAs($this->$user)->getJson('/permissions');

        $response->assertOk()
            ->assertJsonCount($permissionCount + 3, 'data')
            ->assertJsonFragment([[
                $permission1->getKeyName() => $permission1->getKey(),
                'name' => $permission1->name,
                'display_name' => $permission1->display_name,
                'description' => $permission1->description,
                'assignable' => false,
            ],
            ])
            ->assertJsonFragment([[
                $permission2->getKeyName() => $permission2->getKey(),
                'name' => $permission2->name,
                'display_name' => null,
                'description' => $permission2->description,
                'assignable' => false,
            ],
            ])
            ->assertJsonFragment([[
                $permission3->getKeyName() => $permission3->getKey(),
                'name' => $permission3->name,
                'display_name' => null,
                'description' => $permission3->description,
                'assignable' => true,
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexAssignable($user): void
    {
        $this->$user->givePermissionTo('roles.show_details');

        $permission1 = Permission::create([
            'name' => 'permission1',
            'description' => 'Permission 1',
        ]);

        $permission2 = Permission::create([
            'name' => 'permission2',
            'description' => 'Permission 2',
        ]);

        $this->$user->givePermissionTo($permission1);
        $request = $this
            ->actingAs($this->$user)
            ->json('GET', '/permissions', ['assignable' => true]);

        dd($request);

//            ->assertOk()
//            ->assertJsonCount(2, 'data')
//            ->assertJsonFragment([[
//                $permission1->getKeyName() => $permission1->getKey(),
//                'name' => $permission1->name,
//                'display_name' => null,
//                'description' => $permission1->description,
//                'assignable' => true,
//            ],
//            ])
//            ->assertJsonMissing([[
//                $permission2->getKeyName() => $permission2->getKey(),
//                'name' => $permission2->name,
//                'display_name' => null,
//                'description' => $permission2->description,
//                'assignable' => false,
//            ],
//            ])
//            ->assertJsonMissing([
//                'assignable' => false,
//            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexUnassingable($user): void
    {
        $this->$user->givePermissionTo('roles.show_details');

        $permissionCount = Permission::count();

        $permission1 = Permission::create([
            'name' => 'permission1',
            'description' => 'Permission 1',
        ]);

        $permission2 = Permission::create([
            'name' => 'permission2',
            'description' => 'Permission 2',
        ]);

        $this->$user->givePermissionTo($permission2);
        $response = $this->actingAs($this->$user)->json('GET', '/permissions', ['assignable' => false]);

        $response->assertOk()
            ->assertJsonCount($permissionCount, 'data')
            ->assertJsonFragment([[
                $permission1->getKeyName() => $permission1->getKey(),
                'name' => $permission1->name,
                'display_name' => null,
                'description' => $permission1->description,
                'assignable' => false,
            ],
            ])
            ->assertJsonMissing([[
                $permission2->getKeyName() => $permission2->getKey(),
                'name' => $permission2->name,
                'display_name' => null,
                'description' => $permission2->description,
                'assignable' => true,
            ],
            ])
            ->assertJsonMissing([
                'assignable' => true,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithPermissionRolesShowDetails($user): void
    {
        $this->$user->givePermissionTo('roles.show_details');

        $this->actingAs($this->$user)->getJson('/permissions')->assertOk();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithPermissionRolesAdd($user): void
    {
        $this->$user->givePermissionTo('roles.add');

        $this->actingAs($this->$user)->getJson('/permissions')->assertOk();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithPermissionRolesEdit($user): void
    {
        $this->$user->givePermissionTo('roles.edit');

        $this->actingAs($this->$user)->getJson('/permissions')->assertOk();
    }
}
