<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use App\Models\Product;
use App\Models\Role;
use App\Models\Schema;
use Tests\TestCase;

class RoleTest extends TestCase
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
        $role1 = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $role2 = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $response = $this->actingAs($this->user)->getJson('/roles');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                $role1->getKeyName() => $role1->getKey(),
                'name' => $role1->name,
                'description' => $role1->description,
                'assignable' => false,
            ])
            ->assertJsonFragment([
                $role2->getKeyName() => $role2->getKey(),
                'name' => $role2->name,
                'description' => $role2->description,
                'assignable' => false,
            ]);
    }

    public function testShowUnauthorized(): void
    {
        $role = Role::create([
            'name' => 'role1',
            'description' => 'Role 1',
        ]);

        $response = $this->getJson('/roles/id:' . $role->getKey());

        $response->assertUnauthorized();
    }

    public function testShow(): void
    {
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
                'assignable' => false,
                'permissions' => [],
            ]]);
    }
}
