<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use App\Models\Product;
use Tests\TestCase;

class ProductSetUpdateTest extends TestCase
{
    public function testUpdateUnauthorized(): void
    {
        $newSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $set = [
            'name' => 'Test Edit',
            'slug' => 'test-edit',
            'public' => false,
            'hide_on_index' => true,
            'parent_id' => null,
            'children_ids' => [],
        ];

        $response = $this->patchJson('/product-sets/id:' . $newSet->getKey(), $set);
        $response->assertUnauthorized();
    }

    public function testUpdate(): void
    {
        $newSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $set = [
            'name' => 'Test Edit',
            'public' => true,
            'hide_on_index' => true,
        ];

        $parentId = [
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->user)->patchJson(
            '/product-sets/id:' . $newSet->getKey(),
            $set + $parentId + [
                'children_ids' => [],
                'slug_suffix' => 'test-edit',
                'slug_override' => false,
            ],
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $set + [
                'parent' => null,
                'children_ids' => [],
                'slug' => 'test-edit',
                'slug_suffix' => 'test-edit',
                'slug_override' => false,
            ]]);

        $this->assertDatabaseHas('product_sets', $set + $parentId + [
            'slug' => 'test-edit',
        ]);
    }

    public function testUpdateParentSlug(): void
    {
        $parent = ProductSet::factory()->create([
            'name' => 'Parent',
            'slug' => 'parent',
            'hide_on_index' => false,
            'public' => true,
            'public_parent' => false,
        ]);

        $child = ProductSet::factory()->create([
            'parent_id' => $parent->getKey(),
            'name' => 'Child',
            'slug' => 'parent-child',
            'hide_on_index' => false,
            'public' => true,
            'public_parent' => false,
        ]);

        $grandchild = ProductSet::factory()->create([
            'parent_id' => $child->getKey(),
            'name' => 'Grandchild',
            'slug' => 'parent-child-grandchild',
            'hide_on_index' => false,
            'public' => true,
            'public_parent' => false,
        ]);

        $response = $this->actingAs($this->user)->patchJson(
            '/product-sets/id:' . $parent->getKey(),
            [
                'name' => 'New',
                'public' => true,
                'hide_on_index' => false,
                'parent_id' => null,
                'slug_suffix' => 'new',
                'slug_override' => false,
                'children_ids' => [
                    $child->getKey(),
                ],
            ],
        );
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'parent' => null,
                'slug' => 'new',
                'slug_suffix' => 'new',
                'slug_override' => false,
                'children_ids' => [
                    $child->getKey(),
                ],
            ]]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $parent->getKey(),
            'slug' => 'new',
        ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $child->getKey(),
            'slug' => 'new-child',
        ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $grandchild->getKey(),
            'slug' => 'new-child-grandchild',
        ]);
    }

    public function testUpdateParentSlugTree(): void
    {
        $parent = ProductSet::factory()->create([
            'name' => 'Parent',
            'slug' => 'parent',
            'hide_on_index' => false,
            'public' => true,
            'public_parent' => false,
        ]);

        $child = ProductSet::factory()->create([
            'parent_id' => $parent->getKey(),
            'name' => 'Child',
            'slug' => 'parent-child',
            'hide_on_index' => false,
            'public' => true,
            'public_parent' => false,
        ]);

        $grandchild = ProductSet::factory()->create([
            'parent_id' => $child->getKey(),
            'name' => 'Grandchild',
            'slug' => 'parent-child-grandchild',
            'hide_on_index' => false,
            'public' => true,
            'public_parent' => false,
        ]);

        $response = $this->actingAs($this->user)->patchJson(
            '/product-sets/id:' . $parent->getKey() . '?tree',
            [
                'name' => 'New',
                'public' => true,
                'hide_on_index' => false,
                'parent_id' => null,
                'slug_suffix' => 'new',
                'slug_override' => false,
                'children_ids' => [
                    $child->getKey(),
                ],
            ],
        );
        $response
            ->assertOk()
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
                        'parent_id' => $parent->getKey(),
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
                            ]
                        ],
                    ]
                ],
            ]]);
    }
}
