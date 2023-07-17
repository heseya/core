<?php

namespace Tests\Feature;

use App\Models\ProductSet;
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
            'order' => 12,
        ]);

        $this->subChildSet = ProductSet::factory()->create([
            'public' => false,
            'public_parent' => true,
            'parent_id' => $this->childSet->getKey(),
            'order' => 13,
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
        $this->{$user}->givePermissionTo('product_sets.show');

        $this->index($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function index($user): void
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
    public function testIndexByIds($user): void
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
    public function testIndexParentIdEmpty($user): void
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

        $this->assertQueryCountLessThan(10);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexProductsAdd($user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $this->index($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSetsProductsEdit($user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->index($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexHidden($user): void
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
    public function testIndexRoot($user): void
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
    public function testIndexRootHidden($user): void
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
    public function testSearchByParentId($user): void
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
