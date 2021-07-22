<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use App\Models\Product;
use Tests\TestCase;

class ProductSetTest extends TestCase
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
            'order' => 10,
        ]);

        $this->privateSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 11,
        ]);

        $this->childSet = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $this->set->getKey(),
        ]);

        $this->subChildSet = ProductSet::factory()->create([
            'public' => false,
            'parent_id' => $this->childSet->getKey(),
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
                    'slug_override' => false,
                    'public' => $this->set->public,
                    'hide_on_index' => $this->set->hide_on_index,
                    'parent' => $this->set->parent,
                    'children' => [
                        [
                            'id' => $this->childSet->getKey(),
                            'name' => $this->childSet->name,
                            'slug' => $this->childSet->slug,
                            'slug_override' => true,
                            'public' => $this->childSet->public,
                            'hide_on_index' => $this->childSet->hide_on_index,
                            'parent_id' => $this->childSet->parent_id,
                            'children_ids' => [],
                        ],
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
                    'slug_override' => false,
                    'public' => $this->set->public,
                    'hide_on_index' => $this->set->hide_on_index,
                    'parent' => null,
                    'children' => [
                        [
                            'id' => $this->childSet->getKey(),
                            'name' => $this->childSet->name,
                            'slug' => $this->childSet->slug,
                            'slug_override' => true,
                            'public' => $this->childSet->public,
                            'hide_on_index' => $this->childSet->hide_on_index,
                            'parent_id' => $this->childSet->parent_id,
                            'children_ids' => [
                                $this->subChildSet->getKey(),
                            ],
                        ],
                    ],
                ],
                1 => [
                    'id' => $this->privateSet->getKey(),
                    'name' => $this->privateSet->name,
                    'slug' => $this->privateSet->slug,
                    'slug_override' => false,
                    'public' => $this->privateSet->public,
                    'hide_on_index' => $this->privateSet->hide_on_index,
                    'parent' => null,
                    'children' => [],
                ],
            ]]);
    }

    public function testIndexTree(): void
    {
        $response = $this->getJson('/product-sets?tree');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Shoud show only public sets.
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->set->getKey(),
                    'name' => $this->set->name,
                    'slug' => $this->set->slug,
                    'slug_override' => false,
                    'public' => $this->set->public,
                    'hide_on_index' => $this->set->hide_on_index,
                    'parent' => $this->set->parent,
                    'children' => [
                        [
                            'id' => $this->childSet->getKey(),
                            'name' => $this->childSet->name,
                            'slug' => $this->childSet->slug,
                            'slug_override' => true,
                            'public' => $this->childSet->public,
                            'hide_on_index' => $this->childSet->hide_on_index,
                            'parent_id' => $this->childSet->parent_id,
                            'children' => [],
                        ],
                    ],
                ],
            ]]);
    }

    public function testIndexTreeAuthorized(): void
    {
        $response = $this->actingAs($this->user)->getJson('/product-sets?tree');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data') // Shoud show only public sets.
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->set->getKey(),
                    'name' => $this->set->name,
                    'slug' => $this->set->slug,
                    'slug_override' => false,
                    'public' => $this->set->public,
                    'hide_on_index' => $this->set->hide_on_index,
                    'parent' => null,
                    'children' => [
                        [
                            'id' => $this->childSet->getKey(),
                            'name' => $this->childSet->name,
                            'slug' => $this->childSet->slug,
                            'slug_override' => true,
                            'public' => $this->childSet->public,
                            'hide_on_index' => $this->childSet->hide_on_index,
                            'parent_id' => $this->childSet->parent_id,
                            'children' => [
                                [
                                    'id' => $this->subChildSet->getKey(),
                                    'name' => $this->subChildSet->name,
                                    'slug' => $this->subChildSet->slug,
                                    'slug_override' => true,
                                    'public' => $this->subChildSet->public,
                                    'hide_on_index' => $this->subChildSet->hide_on_index,
                                    'parent_id' => $this->subChildSet->parent_id,
                                    'children' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                1 => [
                    'id' => $this->privateSet->getKey(),
                    'name' => $this->privateSet->name,
                    'slug' => $this->privateSet->slug,
                    'slug_override' => false,
                    'public' => $this->privateSet->public,
                    'hide_on_index' => $this->privateSet->hide_on_index,
                    'parent' => null,
                    'children' => [],
                ],
            ]]);
    }

    public function testShowUnauthorized(): void
    {
        $response = $this->getJson('/product-sets/id:' . $this->set->getKey());
        $response->assertUnauthorized();
    }

    public function testShow(): void
    {
        $response = $this->actingAs($this->user)->getJson('/product-sets/id:' . $this->set->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->set->getKey(),
                'name' => $this->set->name,
                'slug' => $this->set->slug,
                'slug_override' => false,
                'public' => $this->set->public,
                'hide_on_index' => $this->set->hide_on_index,
                'parent' => $this->set->parent,
                'children' => [
                    [
                        'id' => $this->childSet->getKey(),
                        'name' => $this->childSet->name,
                        'slug' => $this->childSet->slug,
                        'slug_override' => true,
                        'public' => $this->childSet->public,
                        'hide_on_index' => $this->childSet->hide_on_index,
                        'parent_id' => $this->childSet->parent_id,
                        'children_ids' => [
                            $this->subChildSet->getKey(),
                        ],
                    ],
                ],
            ]]);
    }

    public function testShowPrivate(): void
    {
        $response = $this->actingAs($this->user)->getJson('/product-sets/id:' . $this->privateSet->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->privateSet->getKey(),
                'name' => $this->privateSet->name,
                'slug' => $this->privateSet->slug,
                'slug_override' => false,
                'public' => $this->privateSet->public,
                'hide_on_index' => $this->privateSet->hide_on_index,
                'parent' => null,
                'children' => [],
            ]]);
    }

    public function testShowSlug(): void
    {
        $response = $this->getJson('/product-sets/' . $this->set->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->set->getKey(),
                'name' => $this->set->name,
                'slug' => $this->set->slug,
                'slug_override' => false,
                'public' => $this->set->public,
                'hide_on_index' => $this->set->hide_on_index,
                'parent' => $this->set->parent,
                'children' => [
                    [
                        'id' => $this->childSet->getKey(),
                        'name' => $this->childSet->name,
                        'slug' => $this->childSet->slug,
                        'slug_override' => true,
                        'public' => $this->childSet->public,
                        'hide_on_index' => $this->childSet->hide_on_index,
                        'parent_id' => $this->childSet->parent_id,
                        'children_ids' => [],
                    ],
                ],
            ]]);
    }

    public function testShowSlugAuthorized(): void
    {
        $response = $this->actingAs($this->user)->getJson('/product-sets/' . $this->set->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->set->getKey(),
                'name' => $this->set->name,
                'slug' => $this->set->slug,
                'slug_override' => false,
                'public' => $this->set->public,
                'hide_on_index' => $this->set->hide_on_index,
                'parent' => $this->set->parent,
                'children' => [
                    [
                        'id' => $this->childSet->getKey(),
                        'name' => $this->childSet->name,
                        'slug' => $this->childSet->slug,
                        'slug_override' => true,
                        'public' => $this->childSet->public,
                        'hide_on_index' => $this->childSet->hide_on_index,
                        'parent_id' => $this->childSet->parent_id,
                        'children_ids' => [
                            $this->subChildSet->getKey(),
                        ],
                    ],
                ],
            ]]);
    }

    public function testShowSlugPrivateUnauthorized(): void
    {
        $response = $this->getJson('/product-sets/' . $this->privateSet->slug);
        $response->assertNotFound();
    }

    public function testShowSlugPrivate(): void
    {
        $response = $this->actingAs($this->user)->getJson('/product-sets/' . $this->privateSet->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->privateSet->getKey(),
                'name' => $this->privateSet->name,
                'slug' => $this->privateSet->slug,
                'slug_override' => false,
                'public' => $this->privateSet->public,
                'hide_on_index' => $this->privateSet->hide_on_index,
                'parent' => null,
                'children' => [],
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
            'public' => true,
            'hide_on_index' => false,
        ];

        $response = $this->actingAs($this->user)->postJson('/product-sets', $set);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + $defaults + [
                'parent' => null,
                'slug_override' => false,
            ]]);

        $this->assertDatabaseHas('product_sets', $set + $defaults + [
            'parent_id' => null,
        ]);
    }

    public function testCreateFull(): void
    {
        $set = [
            'name' => 'Test',
            'slug' => 'test',
            'public' => false,
            'hide_on_index' => true,
        ];

        $response = $this->actingAs($this->user)->postJson('/product-sets', $set);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'parent' => null,
                'slug_override' => false,
            ]]);

        $this->assertDatabaseHas('product_sets', $set + [
            'parent_id' => null,
        ]);
    }

    public function testCreateParent(): void
    {
        $set = [
            'name' => 'Test Parent',
            'slug' => 'test-parent',
        ];

        $response = $this->actingAs($this->user)->postJson('/product-sets', $set + [
            'children_ids' => [
                $this->privateSet->getKey(),
                $this->set->getKey(),
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'slug_override' => false,
                'children' => [
                    [
                        'id' => $this->privateSet->getKey(),
                        'name' => $this->privateSet->name,
                        'slug' => 'test-parent-' . $this->privateSet->slug,
                        'slug_override' => false,
                        'public' => $this->privateSet->public,
                        'hide_on_index' => $this->privateSet->hide_on_index,
                        'children_ids' => [],
                    ],
                    [
                        'id' => $this->set->getKey(),
                        'name' => $this->set->name,
                        'slug' => 'test-parent-' . $this->set->slug,
                        'slug_override' => false,
                        'public' => $this->set->public,
                        'hide_on_index' => $this->set->hide_on_index,
                        'children_ids' => [
                            $this->childSet->getKey(),
                        ],
                    ],
                ],
            ]]);

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
            'order' => 15,
        ]);

        $set = [
            'name' => 'Test Child',
            'slug' => 'test-child',
        ];

        $parentId = [
            'parent_id' => $parent->getKey(),
        ];

        $response = $this->actingAs($this->user)->postJson('/product-sets', $set + $parentId);
        $response
            ->assertCreated()
            ->assertJson(['data' => $set + [
                'parent' => [
                    'id' => $parent->getKey(),
                    'name' => $parent->name,
                    'slug' => $parent->slug,
                    'slug_override' => false,
                    'public' => $parent->public,
                    'hide_on_index' => $parent->hide_on_index,
                ]
            ]]);

        $this->assertDatabaseHas('product_sets', $set + $parentId);
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
            'public' => false,
            'order' => 40,
        ]);
        
        $set = [
            'name' => 'Test Edit',
            'slug' => 'test-edit',
            'public' => true,
            'hide_on_index' => true,
        ];

        $parentId = [
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->user)->postJson(
            '/product-sets/id:' . $newSet->getKey(), 
            $set + $parentId + [
                'children_ids' => [],
            ],
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $set + [
                'parent' => null,
                'children' => [],
                'slug_override' => false,
            ]]);

        $this->assertDatabaseHas('product_sets', $set + $parentId);
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
