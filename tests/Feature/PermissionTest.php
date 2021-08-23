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

        $response->assertUnauthorized();
    }

    public function testIndex(): void
    {
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

        $response = $this->actingAs($this->user)->getJson('/permissions');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment([
                $permission1->getKeyName() => $permission1->getKey(),
                'name' => $permission1->name,
                'description' => $permission1->description,
                'assignable' => true,
            ])
            ->assertJsonFragment([
                $permission2->getKeyName() => $permission2->getKey(),
                'name' => $permission2->name,
                'description' => $permission2->description,
                'assignable' => true,
            ])
            ->assertJsonFragment([
                $permission3->getKeyName() => $permission3->getKey(),
                'name' => $permission3->name,
                'description' => $permission3->description,
                'assignable' => true,
            ]);
    }

    public function testIndexAssignable(): void
    {
        $permission1 = Permission::create([
            'name' => 'permission1',
            'description' => 'Permission 1',
        ]);

        $permission2 = Permission::create([
            'name' => 'permission2',
            'description' => 'Permission 2',
        ]);

        $response = $this->actingAs($this->user)->getJson('/permissions?assignable=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                $permission1->getKeyName() => $permission1->getKey(),
                'name' => $permission1->name,
                'description' => $permission1->description,
                'assignable' => true,
            ])
            ->assertJsonFragment([
                $permission2->getKeyName() => $permission2->getKey(),
                'name' => $permission2->name,
                'description' => $permission2->description,
                'assignable' => true,
            ]);

        $this->actingAs($this->user)->getJson('/permissions?assignable=0')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
