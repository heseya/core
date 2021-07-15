<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use App\Models\Product;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProductSetTest extends TestCase
{
    private ProductSet $set;
    private ProductSet $privateSet;
    private ProductSet $childSet;

    public function setUp(): void
    {
        parent::setUp();

        $this->set = ProductSet::factory()->create([
            'public' => true,
            'order' => 0,
        ]);

        $this->privateSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 1,
        ]);

        $this->childSet = ProductSet::factory()->create([
            'public' => true,
            'order' => 0,
            'parent_id' => $this->set->getKey(),
        ]);
    }

    public function testIndex(): void
    {
        $response = $this->getJson('/product-sets');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Shoud show only public sets.
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->set->getKey(),
                    'name' => $this->set->name,
                    'slug' => $this->set->slug,
                    'public' => $this->set->public,
                    'hide_on_index' => $this->set->hide_on_index,
                    'parent_id' => $this->set->parent_id,
                    'children_ids' => [
                        $this->childSet->getKey(),
                    ],
                ],
            ]]);
    }

    public function testIndexAuthorized(): void
    {
        $response = $this->actingAs($this->user)->getJson('/product-sets');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data') // Shoud show only public sets.
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->set->getKey(),
                    'name' => $this->set->name,
                    'slug' => $this->set->slug,
                    'public' => $this->set->public,
                    'hide_on_index' => $this->set->hide_on_index,
                    'parent_id' => $this->set->parent_id,
                    'children_ids' => [
                        $this->childSet->getKey(),
                    ],
                ],
                1 => [
                    'id' => $this->privateSet->getKey(),
                    'name' => $this->privateSet->name,
                    'slug' => $this->privateSet->slug,
                    'public' => $this->privateSet->public,
                    'hide_on_index' => $this->privateSet->hide_on_index,
                    'parent_id' => $this->privateSet->parent_id,
                    'children_ids' => [],
                ],
            ]]);
    }

    public function testCreateUnauthorized(): void
    {
        $set = [
            'name' => 'Test',
            'slug' => 'test',
        ];

        $response = $this->postJson('/product-sets', $set);
        $response->assertUnauthorized();
    }

    public function testCreateMinimal(): void
    {
        $set = [
            'name' => 'Test',
            'slug' => 'test',
        ];

        $defaults = [
            'parent_id' => null,
            'public' => true,
            'hide_on_index' => false,
        ];

        $response = $this->actingAs($this->user)->postJson('/product-sets', $set);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + $defaults]);

        $this->assertDatabaseHas('product_sets', $set + $defaults);
    }

    public function testCreateFull(): void
    {
        $set = [
            'name' => 'Test',
            'slug' => 'test',
            'public' => false,
            'hide_on_index' => true,
        ];

        $defaults = [
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->user)->postJson('/product-sets', $set);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + $defaults]);

        $this->assertDatabaseHas('product_sets', $set + $defaults);
    }

    public function testCreateParent(): void
    {
        $set = [
            'name' => 'Test Parent',
            'slug' => 'test-parent',
        ];

        $children = [
            'children_ids' => [
                $this->privateSet->getKey(),
                $this->set->getKey(),
            ],
        ];

        $response = $this->actingAs($this->user)->postJson('/product-sets', $set + $children);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + $children]);

        $this->assertDatabaseHas('product_sets', $set);

        $parentId = $response->json('data.id');

        $this->assertDatabaseHas('product_sets', [
            'id' => $this->privateSet->getKey(),
            'parent_id' => $parentId,
            'order' => 0,
        ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $this->set->getKey(),
            'parent_id' => $parentId,
            'order' => 1,
        ]);
    }

    public function testCreateChild(): void
    {
        $parent = ProductSet::factory()->create([
            'public' => true,
            'order' => 10,
        ]);

        $set = [
            'name' => 'Test Child',
            'slug' => 'test-child',
            'parent_id' => $parent->getKey(),
        ];

        $response = $this->actingAs($this->user)->postJson('/product-sets', $set);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set]);

        $this->assertDatabaseHas('product_sets', $set);
    }

    public function testCreateOrder(): void
    {
        ProductSet::factory()->create([
            'public' => true,
            'order' => 20,
        ]);

        $set = [
            'name' => 'Test Order',
            'slug' => 'test-order',
        ];

        $order = [
            'order' => 21,
        ];

        $response = $this->actingAs($this->user)->postJson('/product-sets', $set);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set]);

        $this->assertDatabaseHas('product_sets', $set + $order);
    }

    public function testUpdateUnauthorized(): void
    {
        $set = [
            'name' => 'Test Edit',
            'slug' => 'test-edit',
            'public' => false,
            'hide_on_index' => true,
            'parent_id' => null,
            'children_ids' => [],
        ];

        $response = $this->postJson('/product-sets/id:' . $this->set->getKey(), $set);
        $response->assertUnauthorized();
    }

    public function testUpdate(): void
    {
        $newSet = ProductSet::factory()->create([
            'public' => true,
            'order' => 40,
        ]);
        
        $set = [
            'name' => 'Test Edit',
            'slug' => 'test-edit',
            'public' => false,
            'hide_on_index' => true,
            'parent_id' => null,
        ];

        $children = [
            'children_ids' => [],
        ];

        $response = $this->actingAs($this->user)->postJson(
            '/product-sets/id:' . $newSet->getKey(), 
            $set + $children,
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $set + $children]);

        $this->assertDatabaseHas('product_sets', $set);
    }

    public function testDeleteUnauthorized(): void
    {
        $newSet = ProductSet::factory()->create([
            'public' => true,
            'order' => 50,
        ]);

        $response = $this->deleteJson('/product-sets/id:' . $newSet->getKey());
        $response->assertUnauthorized();
    }

    public function testDelete(): void
    {
        $newSet = ProductSet::factory()->create([
            'public' => true,
            'order' => 60,
        ]);

        $this->assertDatabaseHas('product_sets', $newSet->toArray());

        $response = $this->actingAs($this->user)->deleteJson(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertDeleted($newSet);
    }

    public function testDeleteWithRelations(): void
    {
        $set = ProductSet::factory()->create([
            'public' => true,
            'order' => 60,
        ]);

        $set->products()->save(Product::factory()->make());

        $response = $this->actingAs($this->user)->delete(
            '/product-sets/id:' . $set->getKey(),
        );
        $response->assertStatus(400);
        $this->assertDatabaseHas('product_sets', $set->toArray());
    }
}
