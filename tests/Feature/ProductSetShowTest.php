<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use App\Models\SeoMetadata;
use Tests\TestCase;

class ProductSetShowTest extends TestCase
{
    private ProductSet $set;
    private ProductSet $privateSet;
    private ProductSet $childSet;
    private ProductSet $subChildSet;

    private array $expected_structure;

    public function setUp(): void
    {
        parent::setUp();

        $this->set = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'order' => 10,
        ]);

        $this->set->seo()->save(SeoMetadata::factory()->create());

        $this->privateSet = ProductSet::factory()->create([
            'public' => false,
            'public_parent' => true,
            'order' => 11,
        ]);

        $this->privateSet->seo()->save(SeoMetadata::factory()->create());

        $this->childSet = ProductSet::factory()->create([
            'public' => true,
            'public_parent' => true,
            'parent_id' => $this->set->getKey(),
        ]);

        $this->childSet->seo()->save(SeoMetadata::factory()->create());

        $this->subChildSet = ProductSet::factory()->create([
            'public' => false,
            'public_parent' => true,
            'parent_id' => $this->childSet->getKey(),
        ]);

        $this->subChildSet->seo()->save(SeoMetadata::factory()->create());

        $this->expected_structure = [
            'id',
            'name',
            'slug',
            'slug_override',
            'public',
            'visible',
            'hide_on_index',
            'parent',
            'seo',
        ];
    }

    public function testShowUnauthorized(): void
    {
        $response = $this->getJson('/product-sets/id:' . $this->set->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow($user): void
    {
        $this->$user->givePermissionTo('product_sets.show_details');

        $response = $this->actingAs($this->$user)
            ->getJson('/product-sets/id:' . $this->set->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->set->getKey(),
                'name' => $this->set->name,
                'slug' => $this->set->slug,
                'slug_override' => false,
                'public' => $this->set->public,
                'visible' => $this->set->public && $this->set->public_parent,
                'hide_on_index' => $this->set->hide_on_index,
                'parent' => $this->set->parent,
                'children_ids' => [
                    $this->childSet->getKey(),
                ],
                'seo' => [
                    'title' => $this->set->seo->title,
                    'description' => $this->set->seo->description,
                ],
            ]])
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowHiddenUnauthorized($user): void
    {
        $this->$user->givePermissionTo('product_sets.show_details');

        $this
            ->actingAs($this->$user)
            ->getJson('/product-sets/id:' . $this->privateSet->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowHidden($user): void
    {
        $this->$user->givePermissionTo(['product_sets.show_details', 'product_sets.show_hidden']);

        $response = $this->actingAs($this->$user)
            ->getJson('/product-sets/id:' . $this->privateSet->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->privateSet->getKey(),
                'name' => $this->privateSet->name,
                'slug' => $this->privateSet->slug,
                'slug_override' => false,
                'public' => $this->privateSet->public,
                'visible' => $this->privateSet->public && $this->privateSet->public_parent,
                'hide_on_index' => $this->privateSet->hide_on_index,
                'parent' => null,
                'children_ids' => [],
                'seo' => [
                    'title' => $this->privateSet->seo->title,
                    'description' => $this->privateSet->seo->description,
                ],
            ]])
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowTree($user): void
    {
        $this->$user->givePermissionTo('product_sets.show_details');

        $response = $this->actingAs($this->$user)
            ->getJson('/product-sets/id:' . $this->set->getKey() . '?tree');
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->set->getKey(),
                'name' => $this->set->name,
                'slug' => $this->set->slug,
                'slug_override' => false,
                'public' => $this->set->public,
                'visible' => $this->set->public && $this->set->public_parent,
                'hide_on_index' => $this->set->hide_on_index,
                'parent' => $this->set->parent,
                'seo' => [
                    'title' => $this->set->seo->title,
                    'description' => $this->set->seo->description,
                ],
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
                        'children' => null,
                    ],
                ],
            ]])
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowTreeHidden($user): void
    {
        $this->$user->givePermissionTo(['product_sets.show_details', 'product_sets.show_hidden']);

        $response = $this->actingAs($this->$user)
            ->getJson('/product-sets/id:' . $this->set->getKey() . '?tree');
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->set->getKey(),
                'name' => $this->set->name,
                'slug' => $this->set->slug,
                'slug_override' => false,
                'public' => $this->set->public,
                'visible' => $this->set->public && $this->set->public_parent,
                'hide_on_index' => $this->set->hide_on_index,
                'parent' => $this->set->parent,
                'seo' => [
                    'title' => $this->set->seo->title,
                    'description' => $this->set->seo->description,
                ],
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
            ]])
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowSlugUnauthorized($user): void
    {
        $response = $this->actingAs($this->$user)
            ->getJson('/product-sets/' . $this->set->slug);
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowSlug($user): void
    {
        $this->$user->givePermissionTo('product_sets.show_details');

        $response = $this->actingAs($this->$user)
            ->getJson('/product-sets/' . $this->set->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->set->getKey(),
                'name' => $this->set->name,
                'slug' => $this->set->slug,
                'slug_override' => false,
                'public' => $this->set->public,
                'visible' => $this->set->public && $this->set->public_parent,
                'hide_on_index' => $this->set->hide_on_index,
                'parent' => $this->set->parent,
                'children_ids' => [
                    $this->childSet->getKey(),
                ],
                'seo' => [
                    'title' => $this->set->seo->title,
                    'description' => $this->set->seo->description,
                ],
            ]])
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowSlugHiddenUnauthorized($user): void
    {
        $this->$user->givePermissionTo('product_sets.show_details');

        $response = $this->actingAs($this->$user)
            ->getJson('/product-sets/' . $this->privateSet->slug);
        $response->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowSlugHidden($user): void
    {
        $this->$user->givePermissionTo(['product_sets.show_details', 'product_sets.show_hidden']);

        $response = $this->actingAs($this->$user)->getJson('/product-sets/' . $this->privateSet->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->privateSet->getKey(),
                'name' => $this->privateSet->name,
                'slug' => $this->privateSet->slug,
                'slug_override' => false,
                'public' => $this->privateSet->public,
                'visible' => $this->privateSet->public && $this->privateSet->public_parent,
                'hide_on_index' => $this->privateSet->hide_on_index,
                'parent' => null,
                'children_ids' => [],
                'seo' => [
                    'title' => $this->privateSet->seo->title,
                    'description' => $this->privateSet->seo->description,
                ],
            ]])
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowSlugTree($user): void
    {
        $this->$user->givePermissionTo('product_sets.show_details');

        $response = $this->actingAs($this->$user)
            ->getJson('/product-sets/' . $this->set->slug . '?tree');
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->set->getKey(),
                'name' => $this->set->name,
                'slug' => $this->set->slug,
                'slug_override' => false,
                'public' => $this->set->public,
                'visible' => $this->set->public && $this->set->public_parent,
                'hide_on_index' => $this->set->hide_on_index,
                'parent' => $this->set->parent,
                'seo' => [
                    'title' => $this->set->seo->title,
                    'description' => $this->set->seo->description,
                ],
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
                        'children' => null,
                    ],
                ],
            ]])
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowSlugTreeHidden($user): void
    {
        $this->$user->givePermissionTo(['product_sets.show_details', 'product_sets.show_hidden']);

        $response = $this->actingAs($this->$user)
            ->getJson('/product-sets/' . $this->set->slug . '?tree');
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->set->getKey(),
                'name' => $this->set->name,
                'slug' => $this->set->slug,
                'slug_override' => false,
                'public' => $this->set->public,
                'visible' => $this->set->public && $this->set->public_parent,
                'hide_on_index' => $this->set->hide_on_index,
                'parent' => $this->set->parent,
                'seo' => [
                    'title' => $this->set->seo->title,
                    'description' => $this->set->seo->description,
                ],
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
            ]])
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }
}
