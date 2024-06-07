<?php

namespace Tests\Feature;

use Domain\ProductSet\ProductSet;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Tests\Traits\JsonQueryCounter;

class ProductSetIndexTest extends TestCase
{
    use JsonQueryCounter;

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
            'created_at' => now()->subDays(4),
        ]);

        $this->privateSet = ProductSet::factory()->create([
            'public' => false,
            'public_parent' => true,
            'order' => 11,
            'created_at' => now()->subDays(3),
        ]);

        $this->childSet = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'parent_id' => $this->set->getKey(),
            'order' => 12,
            'created_at' => now()->subDays(2),
        ]);

        $this->subChildSet = ProductSet::factory()->create([
            'public' => false,
            'public_parent' => true,
            'parent_id' => $this->childSet->getKey(),
            'order' => 13,
            'created_at' => now()->subDays(1),
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
    public function testIndexSetsShow(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show');

        $this->index($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function index(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show');

        $response = $this->actingAs($this->{$user})->getJson('/product-sets');
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
                    'parent_id' => $this->childSet->parent_id,
                    'children_ids' => [],
                ],
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByIds(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show');

        $response = $this->actingAs($this->{$user})->json('GET', '/product-sets', [
            'ids' => [
                $this->set->getKey(),
            ],
        ]);
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->set->getKey(),
                    'name' => $this->set->name,
                    'slug' => $this->set->slug,
                    'slug_override' => false,
                    'public' => $this->set->public,
                    'visible' => $this->set->public && $this->set->public_parent,
                    'parent_id' => $this->set->parent_id,
                    'children_ids' => [
                        $this->childSet->getKey(),
                    ],
                ],
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexParentIdEmpty(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show');

        $response = $this->actingAs($this->{$user})->getJson('/product-sets?parent_id=');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data') // Should show only public sets.
            ->assertJson(['data' => [
                0 => [
                    'id' => $this->set->getKey(),
                    'name' => $this->set->name,
                    'slug' => $this->set->slug,
                    'slug_override' => false,
                    'public' => $this->set->public,
                    'visible' => $this->set->public && $this->set->public_parent,
                    'parent_id' => $this->set->parent_id,
                    'children_ids' => [
                        $this->childSet->getKey(),
                    ],
                ],
            ],
            ]);
    }

    public function testIndexPerformance(): void
    {
        $this->user->givePermissionTo('product_sets.show');

        ProductSet::factory()->count(498)->create([
            'public' => true,
        ]);

        $this
            ->actingAs($this->user)
            ->json('GET', '/product-sets', ['limit' => 500])
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(12);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexProductsAdd(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $this->index($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSetsProductsEdit(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->index($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden(string $user): void
    {
        $this->{$user}->givePermissionTo(['product_sets.show', 'product_sets.show_hidden']);

        $response = $this->actingAs($this->{$user})->getJson('/product-sets');
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
                    'parent_id' => $this->subChildSet->parent_id,
                    'children_ids' => [],
                ],
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexRoot(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show');

        $response = $this->actingAs($this->{$user})->json('GET', '/product-sets', ['root' => true]);
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
                    'parent_id' => $this->set->parent_id,
                    'children_ids' => [
                        $this->childSet->getKey(),
                    ],
                ],
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexRootHidden(string $user): void
    {
        $this->{$user}->givePermissionTo(['product_sets.show', 'product_sets.show_hidden']);

        $response = $this->actingAs($this->{$user})->json('GET', '/product-sets', ['root' => true]);
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
                    'parent_id' => null,
                    'children_ids' => [],
                ],
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByParentId(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show');

        $parent = ProductSet::factory()->create([
            'public' => true,
        ]);

        $set = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $parent->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/product-sets', ['parent_id' => $parent->getKey()])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $set->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexPagination(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show');

        $this->set->update([
            'name' => 'first',
            'public' => true,
            'order' => 0,
        ]);

        $this->privateSet->update([
            'name' => 'second',
            'public' => true,
            'order' => 0,
        ]);

        $this->childSet->update([
            'name' => 'third',
            'public' => true,
            'order' => 0,
        ]);

        $this->subChildSet->update([
            'name' => 'fourth',
            'public' => true,
            'order' => 0,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/product-sets', ['limit' => 2])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $this->set->getKey())
            ->assertJsonPath('data.1.id', $this->privateSet->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/product-sets', ['limit' => 2, 'page' => 2])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $this->childSet->getKey())
            ->assertJsonPath('data.1.id', $this->subChildSet->getKey());
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchBySlugSuffix(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show');

        $parent = ProductSet::factory()->create([
            'public' => true,
            'slug' => 'parent',
        ]);

        $set = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $parent->getKey(),
            'slug' => 'parent-child',
        ]);

        $subChild = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $set->getKey(),
            'slug' => 'parent-child-sub-child',
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/product-sets', ['slug_suffix' => 'child'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $set->getKey(),
                'slug_suffix' => 'child',
            ])
            ->assertJsonMissing([
                'id' => $parent->getKey(),
                'slug_suffix' => 'parent',
            ])
            ->assertJsonMissing([
                'id' => $subChild->getKey(),
                'slug_suffix' => 'sub-child',
            ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/product-sets', ['slug_suffix' => 'parent'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $parent->getKey(),
                'slug_suffix' => 'parent',
            ]);
    }

    private function prepareProductSets(): Collection
    {
        ProductSet::query()->delete();

        $setOne = ProductSet::factory()->create([
            'name' => 'abc',
            'public' => true,
        ]);

        $setTwo = ProductSet::factory()->create([
            'name' => 'child',
            'public' => true,
        ]);

        $setThree = ProductSet::factory()->create([
            'name' => 'test',
            'public' => true,
        ]);

        $setTwo->children()->save($setThree);
        $setOne->children()->save($setTwo);

        ProductSet::factory()->create([
            'name' => 'def',
            'public' => true,
        ]);

        ProductSet::factory()->create([
            'name' => 'ghi',
            'public' => true,
        ]);

        return Collection::make([
            'setOne' => $setOne,
            'setTwo' => $setTwo,
            'setThree' => $setThree,
        ]);
    }
}
