<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use Tests\TestCase;

class ProductSetIndexTest extends TestCase
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

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/product-sets');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSetsShow($user): void
    {
        $this->$user->givePermissionTo('product_sets.show');

        $this->index($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function index($user): void
    {
        $this->$user->givePermissionTo('product_sets.show');

        $response = $this->actingAs($this->$user)->getJson('/product-sets');
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data') // Should show only public sets.
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->set->getKey(),
                    'name' => $this->set->name,
                    'slug' => $this->set->slug,
                    'slug_override' => false,
                    'public' => $this->set->public,
                    'visible' => $this->set->public && $this->set->public_parent,
                    'hide_on_index' => $this->set->hide_on_index,
                    'parent_id' => $this->set->parent_id,
                    'children_ids' => [
                        $this->childSet->getKey(),
                    ],
                ],
                1 => [
                    'id' => $this->childSet->getKey(),
                    'name' => $this->childSet->name,
                    'slug' => $this->childSet->slug,
                    'slug_override' => true,
                    'public' => $this->childSet->public,
                    'visible' => $this->childSet->public && $this->childSet->public_parent,
                    'hide_on_index' => $this->childSet->hide_on_index,
                    'parent_id' => $this->childSet->parent_id,
                    'children_ids' => [],
                ],
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexProductsAdd($user): void
    {
        $this->$user->givePermissionTo('products.add');

        $this->index($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSetsProductsEdit($user): void
    {
        $this->$user->givePermissionTo('products.edit');

        $this->index($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden($user): void
    {
        $this->$user->givePermissionTo(['product_sets.show', 'product_sets.show_hidden']);

        $response = $this->actingAs($this->$user)->getJson('/product-sets');
        $response
            ->assertOk()
            ->assertJsonCount(4, 'data') // Should show only public sets.
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->set->getKey(),
                    'name' => $this->set->name,
                    'slug' => $this->set->slug,
                    'slug_override' => false,
                    'public' => $this->set->public,
                    'visible' => $this->set->public && $this->set->public_parent,
                    'hide_on_index' => $this->set->hide_on_index,
                    'parent_id' => null,
                    'children_ids' => [
                        $this->childSet->getKey(),
                    ],
                ],
                1 => [
                    'id' => $this->privateSet->getKey(),
                    'name' => $this->privateSet->name,
                    'slug' => $this->privateSet->slug,
                    'slug_override' => false,
                    'public' => $this->privateSet->public,
                    'visible' => $this->privateSet->public && $this->privateSet->public_parent,
                    'hide_on_index' => $this->privateSet->hide_on_index,
                    'parent_id' => null,
                    'children_ids' => [],
                ],
                2 => [
                    'id' => $this->childSet->getKey(),
                    'name' => $this->childSet->name,
                    'slug' => $this->childSet->slug,
                    'slug_override' => true,
                    'public' => $this->childSet->public,
                    'visible' => $this->childSet->public && $this->childSet->public_parent,
                    'hide_on_index' => $this->childSet->hide_on_index,
                    'parent_id' => $this->childSet->parent_id,
                    'children_ids' => [
                        $this->subChildSet->getKey(),
                    ],
                ],
                3 => [
                    'id' => $this->subChildSet->getKey(),
                    'name' => $this->subChildSet->name,
                    'slug' => $this->subChildSet->slug,
                    'slug_override' => true,
                    'public' => $this->subChildSet->public,
                    'visible' => $this->subChildSet->public && $this->subChildSet->public_parent,
                    'hide_on_index' => $this->subChildSet->hide_on_index,
                    'parent_id' => $this->subChildSet->parent_id,
                    'children_ids' => [],
                ],
            ],
            ]);
    }

    /**
     * @dataProvider trueBooleanProvider
     */
    public function testIndexRoot($user, $boolean, $booleanValue): void
    {
        $this->$user->givePermissionTo('product_sets.show');

        $response = $this->actingAs($this->$user)->json('GET', '/product-sets', ['root' => $boolean]);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public sets.
            ->assertJson(['data' => [
                [
                    'id' => $this->set->getKey(),
                    'name' => $this->set->name,
                    'slug' => $this->set->slug,
                    'slug_override' => false,
                    'public' => $this->set->public,
                    'visible' => $this->set->public && $this->set->public_parent,
                    'hide_on_index' => $this->set->hide_on_index,
                    'parent_id' => $this->set->parent_id,
                    'children_ids' => [
                        $this->childSet->getKey(),
                    ],
                ],
            ],
            ]);
    }

    /**
     * @dataProvider trueBooleanProvider
     */
    public function testIndexRootHidden($user, $boolean, $booleanValue): void
    {
        $this->$user->givePermissionTo(['product_sets.show', 'product_sets.show_hidden']);

        $response = $this->actingAs($this->$user)->json('GET', '/product-sets', ['root' => $boolean]);
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data') // Should show only public sets.
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->set->getKey(),
                    'name' => $this->set->name,
                    'slug' => $this->set->slug,
                    'slug_override' => false,
                    'public' => $this->set->public,
                    'visible' => $this->set->public && $this->set->public_parent,
                    'hide_on_index' => $this->set->hide_on_index,
                    'parent_id' => null,
                    'children_ids' => [
                        $this->childSet->getKey(),
                    ],
                ],
                1 => [
                    'id' => $this->privateSet->getKey(),
                    'name' => $this->privateSet->name,
                    'slug' => $this->privateSet->slug,
                    'slug_override' => false,
                    'public' => $this->privateSet->public,
                    'visible' => $this->privateSet->public && $this->privateSet->public_parent,
                    'hide_on_index' => $this->privateSet->hide_on_index,
                    'parent_id' => null,
                    'children_ids' => [],
                ],
            ],
            ]);
    }

    /**
     * @dataProvider trueBooleanProvider
     */
    public function testIndexTree($user, $boolean, $booleanValue): void
    {
        $this->$user->givePermissionTo('product_sets.show');

        $response = $this->actingAs($this->$user)->json('GET', '/product-sets', ['tree' => $boolean]);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public sets.
            ->assertJson(['data' => [
                [
                    'id' => $this->set->getKey(),
                    'name' => $this->set->name,
                    'slug' => $this->set->slug,
                    'slug_override' => false,
                    'public' => $this->set->public,
                    'visible' => $this->set->public && $this->set->public_parent,
                    'hide_on_index' => $this->set->hide_on_index,
                    'parent_id' => $this->set->parent_id,
                    'cover' => [],
                    'children' => [
                        [
                            'id' => $this->childSet->getKey(),
                            'name' => $this->childSet->name,
                            'slug' => $this->childSet->slug,
                            'slug_override' => true,
                            'public' => $this->childSet->public,
                            'visible' => $this->childSet->public && $this->childSet->public_parent,
                            'hide_on_index' => $this->childSet->hide_on_index,
                            'parent_id' => $this->childSet->parent_id,
                            'cover' => [],
                            'children' => [],
                        ],
                    ],
                ],
            ],
            ]);
    }

    /**
     * @dataProvider trueBooleanProvider
     */
    public function testIndexTreeHidden($user, $boolean, $booleanValue): void
    {
        $this->$user->givePermissionTo(['product_sets.show', 'product_sets.show_hidden']);

        $response = $this->actingAs($this->$user)->json('GET', '/product-sets?tree', ['tree' => $boolean]);
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data') // Should show only public sets.
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->set->getKey(),
                    'name' => $this->set->name,
                    'slug' => $this->set->slug,
                    'slug_override' => false,
                    'public' => $this->set->public,
                    'visible' => $this->set->public && $this->set->public_parent,
                    'hide_on_index' => $this->set->hide_on_index,
                    'parent_id' => null,
                    'children' => [
                        [
                            'id' => $this->childSet->getKey(),
                            'name' => $this->childSet->name,
                            'slug' => $this->childSet->slug,
                            'slug_override' => true,
                            'public' => $this->childSet->public,
                            'visible' => $this->childSet->public && $this->childSet->public_parent,
                            'hide_on_index' => $this->childSet->hide_on_index,
                            'parent_id' => $this->childSet->parent_id,
                            'children' => [
                                [
                                    'id' => $this->subChildSet->getKey(),
                                    'name' => $this->subChildSet->name,
                                    'slug' => $this->subChildSet->slug,
                                    'slug_override' => true,
                                    'public' => $this->subChildSet->public,
                                    'visible' => $this->subChildSet->public && $this->subChildSet->public_parent,
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
                    'visible' => $this->privateSet->public && $this->privateSet->public_parent,
                    'hide_on_index' => $this->privateSet->hide_on_index,
                    'parent_id' => null,
                    'children' => [],
                ],
            ],
            ]);
    }
}
