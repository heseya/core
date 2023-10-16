<?php

namespace Tests\Feature;

use App\Events\ProductSetUpdated;
use App\Models\Attribute;
use App\Models\ProductSet;
use App\Models\SeoMetadata;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProductSetUpdateTest extends TestCase
{
    public function testUpdateUnauthorized(): void
    {
        Event::fake([ProductSetUpdated::class]);

        $newSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $set = [
            'name' => 'Test Edit',
            'slug' => 'test-edit',
            'public' => false,
            'parent_id' => null,
            'children_ids' => [],
        ];

        $response = $this->patchJson('/product-sets/id:' . $newSet->getKey(), $set);
        $response->assertForbidden();

        Event::assertNotDispatched(ProductSetUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $newSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $set = [
            'name' => 'Test Edit',
            'public' => true,
        ];

        $parentId = [
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
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
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $parentId + [
            'slug' => 'test-edit',
        ]);

        Event::assertDispatched(ProductSetUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateTreeFalse($user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $newSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $set = [
            'name' => 'Test Edit',
            'public' => true,
        ];

        $parentId = [
            'parent_id' => null,
        ];

        $this
            ->actingAs($this->{$user})
            ->patchJson(
                '/product-sets/id:' . $newSet->getKey() . '?tree=0',
                $set + $parentId + [
                    'children_ids' => [],
                    'slug_suffix' => 'test-edit',
                    'slug_override' => false,
                ],
            )
            ->assertOk()
            ->assertJson([
                'data' => $set + [
                    'parent' => null,
                    'children_ids' => [],
                    'slug' => 'test-edit',
                    'slug_suffix' => 'test-edit',
                    'slug_override' => false,
                ],
            ])
            ->assertJsonMissing(['data' => 'children']);

        $this->assertDatabaseHas(
            'product_sets',
            $set + $parentId + [
                'slug' => 'test-edit',
            ],
        );

        Event::assertDispatched(ProductSetUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithPartialData($user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $newSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $set = [
            'name' => 'Test Edit',
        ];

        $parentId = [
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
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
            ],
            ]);

        $this->assertDatabaseHas('product_sets', [
            'name' => 'Test Edit',
            'public' => false,
        ] +
            $parentId + [
                'slug' => 'test-edit',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateParentSlug($user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $parent = ProductSet::factory()->create([
            'name' => 'Parent',
            'slug' => 'parent',
            'public' => true,
            'public_parent' => false,
        ]);

        $child = ProductSet::factory()->create([
            'parent_id' => $parent->getKey(),
            'name' => 'Child',
            'slug' => 'parent-child',
            'public' => true,
            'public_parent' => false,
        ]);

        $grandchild = ProductSet::factory()->create([
            'parent_id' => $child->getKey(),
            'name' => 'Grandchild',
            'slug' => 'parent-child-grandchild',
            'public' => true,
            'public_parent' => false,
        ]);

        $response = $this->actingAs($this->{$user})->patchJson(
            '/product-sets/id:' . $parent->getKey(),
            [
                'name' => 'New',
                'public' => true,
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
            ],
            ]);

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

        Event::assertDispatched(ProductSetUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateParentSlugTree($user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $parent = ProductSet::factory()->create([
            'name' => 'Parent',
            'slug' => 'parent',
            'public' => true,
            'public_parent' => false,
        ]);

        $child = ProductSet::factory()->create([
            'parent_id' => $parent->getKey(),
            'name' => 'Child',
            'slug' => 'parent-child',
            'public' => true,
            'public_parent' => false,
        ]);

        $grandchild = ProductSet::factory()->create([
            'parent_id' => $child->getKey(),
            'name' => 'Grandchild',
            'slug' => 'parent-child-grandchild',
            'public' => true,
            'public_parent' => false,
        ]);

        $response = $this->actingAs($this->{$user})->patchJson(
            '/product-sets/id:' . $parent->getKey() . '?tree=1',
            [
                'name' => 'New',
                'public' => true,
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
                                'parent_id' => $child->getKey(),
                                'children' => [],
                            ],
                        ],
                    ],
                ],
            ],
            ]);

        Event::assertDispatched(ProductSetUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithSeo($user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        $newSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $set = [
            'name' => 'Test Edit',
            'public' => true,
        ];

        $seo = SeoMetadata::factory()->create();
        $newSet->seo()->save($seo);

        $parentId = [
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
            '/product-sets/id:' . $newSet->getKey(),
            $set + $parentId + [
                'children_ids' => [],
                'slug_suffix' => 'test-edit',
                'slug_override' => false,
                'seo' => [
                    'title' => 'seo title',
                    'description' => 'seo description',
                ],
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
                'seo' => [
                    'title' => 'seo title',
                    'description' => 'seo description',
                ],
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $parentId + [
            'slug' => 'test-edit',
        ]);
        $this->assertDatabaseHas('seo_metadata', [
            'title' => 'seo title',
            'description' => 'seo description',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithAttributes($user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $newSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $attrOne = Attribute::factory()->create();
        $attrTwo = Attribute::factory()->create();
        $attrThree = Attribute::factory()->create();

        $newSet->attributes()->sync($attrOne->getKey());

        $set = [
            'name' => 'Test Edit',
            'public' => true,
        ];

        $parentId = [
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
            '/product-sets/id:' . $newSet->getKey(),
            $set + $parentId + [
                'children_ids' => [],
                'slug_suffix' => 'test-edit',
                'slug_override' => false,
                'attributes' => [
                    $attrTwo->getKey(),
                    $attrThree->getKey(),
                ],
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
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $parentId + [
            'slug' => 'test-edit',
        ]);

        $this->assertTrue(!$newSet->attributes->contains($attrOne));
        $this->assertTrue($newSet->attributes->contains($attrTwo));
        $this->assertTrue($newSet->attributes->contains($attrThree));

        Event::assertDispatched(ProductSetUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithEmptyAttributes($user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $newSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $attrOne = Attribute::factory()->create();

        $newSet->attributes()->sync($attrOne->getKey());

        $set = [
            'name' => 'Test Edit',
            'public' => true,
        ];

        $parentId = [
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
            '/product-sets/id:' . $newSet->getKey(),
            $set + $parentId + [
                'children_ids' => [],
                'slug_suffix' => 'test-edit',
                'slug_override' => false,
                'attributes' => [],
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
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $parentId + [
            'slug' => 'test-edit',
        ]);

        $this->assertTrue(!$newSet->attributes->contains($attrOne));

        Event::assertDispatched(ProductSetUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithoutAttributes($user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $newSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $attrOne = Attribute::factory()->create();

        $newSet->attributes()->sync($attrOne->getKey());

        $set = [
            'name' => 'Test Edit',
            'public' => true,
        ];

        $parentId = [
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
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
            ],
            ]);

        $this->assertDatabaseHas('product_sets', $set + $parentId + [
            'slug' => 'test-edit',
        ]);

        $this->assertTrue($newSet->attributes->contains($attrOne));

        Event::assertDispatched(ProductSetUpdated::class);
    }
}
