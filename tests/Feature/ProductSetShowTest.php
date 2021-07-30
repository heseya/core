<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use App\Models\Product;
use Tests\TestCase;

class ProductSetShowTest extends TestCase
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
                'visible' => $this->set->public && $this->set->public_parent,
                'hide_on_index' => $this->set->hide_on_index,
                'parent' => $this->set->parent,
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
                'visible' => $this->privateSet->public && $this->privateSet->public_parent,
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
                'visible' => $this->set->public && $this->set->public_parent,
                'hide_on_index' => $this->set->hide_on_index,
                'parent' => $this->set->parent,
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
                'visible' => $this->set->public && $this->set->public_parent,
                'hide_on_index' => $this->set->hide_on_index,
                'parent' => $this->set->parent,
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
                'visible' => $this->privateSet->public && $this->privateSet->public_parent,
                'hide_on_index' => $this->privateSet->hide_on_index,
                'parent' => null,
                'children' => [],
            ]]);
    }
}
