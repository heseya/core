<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use Tests\TestCase;

class ProductSetCreateTest extends TestCase
{
    private ProductSet $set;
    private ProductSet $privateSet;
    private ProductSet $childSet;
    private ProductSet $subChildSet;

    public function setUp(): void
    {
        parent::setUp();

        $this->set = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'order' => 10,
        ]);

        $this->privateSet = ProductSet::factory()->create([
            'public' => false,
            'public_parent' => true,
            'order' => 11,
        ]);

        $this->childSet = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'parent_id' => $this->set->getKey(),
        ]);

        $this->subChildSet = ProductSet::factory()->create([
            'public' => false,
            'public_parent' => true,
            'parent_id' => $this->childSet->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateUnauthorized(): void
    {
        $set = [
            'name' => 'Test',
            'slug' => 'test',
        ];

        $response = $this->postJson('/product-sets', $set);
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateMinimal($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        $set = [
            'name' => 'Test',
        ];

        $defaults = [
            'public' => true,
            'hide_on_index' => false,
            'slug' => 'test',
        ];

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + [
            'slug_suffix' => 'test',
            'slug_override' => false,
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + $defaults + [
                'slug_override' => false,
                'slug_suffix' => 'test',
                'parent' => null,
            ]]);

        $this->assertDatabaseHas('product_sets', $set + $defaults + [
            'parent_id' => null,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateFull($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        $set = [
            'name' => 'Test',
            'public' => false,
            'hide_on_index' => true,
        ];

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + [
            'slug_suffix' => 'test',
            'slug_override' => false,
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'slug_suffix' => 'test',
                'slug_override' => false,
                'parent' => null,
                'slug' => 'test',
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + [
            'parent_id' => null,
            'slug' => 'test',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateParent($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        $set = [
            'name' => 'Test Parent',
        ];

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + [
            'slug_override' => false,
            'slug_suffix' => 'test-parent',
            'children_ids' => [
                $this->privateSet->getKey(),
                $this->set->getKey(),
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'slug_override' => false,
                'slug_suffix' => 'test-parent',
                'slug' => 'test-parent',
                'children_ids' => [
//                    $this->privateSet->getKey(),
                    $this->set->getKey(),
                ],
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + [
            'slug' => 'test-parent',
        ]);

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

    /**
     * @dataProvider authProvider
     */
    public function testCreateChild($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        $parent = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'order' => 15,
        ]);

        $set = [
            'name' => 'Test Child',
        ];

        $parentId = [
            'parent_id' => $parent->getKey(),
        ];

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + $parentId + [
            'slug_suffix' => 'test-child',
            'slug_override' => false,
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'slug' => $parent->slug . '-test-child',
                'slug_suffix' => 'test-child',
                'slug_override' => false,
                'parent' => [
                    'id' => $parent->getKey(),
                    'name' => $parent->name,
                    'slug' => $parent->slug,
                    'slug_suffix' => $parent->slugSuffix,
                    'slug_override' => false,
                    'public' => $parent->public,
                    'visible' => $parent->public && $parent->public_parent,
                    'hide_on_index' => $parent->hide_on_index,
                ],
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $parentId + [
            'slug' => $parent->slug . '-test-child',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrder($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        ProductSet::factory()->create([
            'public' => true,
            'order' => 20,
        ]);

        $set = [
            'name' => 'Test Order',
        ];

        $order = [
            'order' => 21,
        ];

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + [
            'slug_suffix' => 'test-order',
            'slug_override' => false,
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'slug' => 'test-order',
                'slug_suffix' => 'test-order',
                'slug_override' => false,
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $order + [
            'slug' => 'test-order',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateChildVisibility($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        $parent = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => false,
            'order' => 15,
        ]);

        $set = [
            'name' => 'Test Child',
            'public' => true,
        ];

        $parentId = [
            'parent_id' => $parent->getKey(),
        ];

        $response = $this->actingAs($this->$user)->postJson('/product-sets', $set + $parentId + [
            'slug_suffix' => 'test-child',
            'slug_override' => true,
        ]);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'visible' => false,
                'slug' => 'test-child',
                'slug_suffix' => 'test-child',
                'slug_override' => true,
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $parentId + [
            'public_parent' => false,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateDuplicateSlug($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        ProductSet::factory()->create([
            'name' => 'Test duplicate',
            'slug' => 'test-duplicate',
        ]);

        $response = $this->actingAs($this->$user)->postJson('/product-sets', [
            'name' => 'New set',
            'slug_suffix' => 'test-duplicate',
            'slug_override' => false,
        ]);
        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateTreeView($user): void
    {
        $this->$user->givePermissionTo('product_sets.add');

        $child = ProductSet::factory()->create([
            'parent_id' => 'null',
            'name' => 'Child',
            'slug' => 'child',
            'hide_on_index' => false,
            'public' => true,
            'public_parent' => false,
        ]);

        $grandchild = ProductSet::factory()->create([
            'parent_id' => $child->getKey(),
            'name' => 'Grandchild',
            'slug' => 'child-grandchild',
            'hide_on_index' => false,
            'public' => true,
            'public_parent' => false,
        ]);

        $response = $this->actingAs($this->$user)->postJson('/product-sets?tree', [
            'name' => 'New',
            'slug_override' => false,
            'slug_suffix' => 'new',
            'children_ids' => [
                $child->getKey(),
            ],
        ]);
        $parentId = $response->json('data.id');
        $response
            ->assertCreated()
            ->assertJson(['data' => [
                'parent' => null,
                'slug' => 'new',
                'slug_suffix' => 'new',
                'slug_override' => false,
                'children' => [
                    [
                        'id' => $child->getKey(),
                        'name' => 'Child',
                        'slug' => 'new-child',
                        'slug_suffix' => 'child',
                        'slug_override' => false,
                        'public' => true,
                        'visible' => true,
                        'hide_on_index' => false,
                        'parent_id' => $parentId,
                        'children' => [
                            [
                                'id' => $grandchild->getKey(),
                                'name' => 'Grandchild',
                                'slug' => 'new-child-grandchild',
                                'slug_suffix' => 'grandchild',
                                'slug_override' => false,
                                'public' => true,
                                'visible' => true,
                                'hide_on_index' => false,
                                'parent_id' => $child->getKey(),
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
