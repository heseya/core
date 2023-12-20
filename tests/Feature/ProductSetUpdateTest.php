<?php

namespace Tests\Feature;

use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductSet\Events\ProductSetUpdated;
use Domain\ProductSet\ProductSet;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class ProductSetUpdateTest extends TestCase
{
    public function testUpdateUnauthorized(): void
    {
        Event::fake([ProductSetUpdated::class]);

        $newSet = ProductSet::factory()->create();

        $response = $this->patchJson('/product-sets/id:' . $newSet->getKey());
        $response->assertForbidden();

        Event::assertNotDispatched(ProductSetUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        /** @var ProductSet $set */
        $set = ProductSet::factory()->create();

        /** @var Attribute $attribute */
        $attribute = Attribute::factory()->create();
        $set->attributes()->sync($attribute->getKey());

        $this
            ->actingAs($this->{$user})
            ->patchJson("/product-sets/id:{$set->getKey()}", [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test Edit',
                    ],
                ],
                'parent_id' => null,
                'children_ids' => [],
                'slug_suffix' => 'test-edit',
                'slug_override' => false,
            ])
            ->assertOk()
            ->assertJson([
                'data' => [
                    'name' => 'Test Edit',
                    'parent' => null,
                    'children_ids' => [],
                    'slug' => 'test-edit',
                    'slug_suffix' => 'test-edit',
                    'slug_override' => false,
                ],
            ]);

        $this->assertDatabaseHas('product_sets', [
            "name->{$this->lang}" => 'Test Edit',
            'parent_id' => null,
            'slug' => 'test-edit',
        ]);

        // attributes should remain the same
        $this->assertTrue($set->attributes->contains($attribute));

        Event::assertDispatched(ProductSetUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateEmptyDescription(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        /** @var ProductSet $set */
        $set = ProductSet::factory()->create([
            'description_html' => null,
        ]);

        $this
            ->actingAs($this->{$user})
            ->patchJson("/product-sets/id:{$set->getKey()}", [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test Edit',
                        'description_html' => null,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJson([
                'data' => [
                    'name' => 'Test Edit',
                    'description_html' => '',
                ],
            ]);

        $this->assertDatabaseHas('product_sets', [
            "name->{$this->lang}" => 'Test Edit',
            "description_html->{$this->lang}" => null,
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
            'public' => true,
            'translations' => [
                $this->lang => [
                    'name' => 'Test Edit',
                ],
            ],
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
                'data' => [
                    'public' => true,
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
            $parentId + [
                'public' => true,
                'slug' => 'test-edit',
                "name->{$this->lang}" => 'Test Edit',
            ],
        );

        Event::assertDispatched(ProductSetUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithPartialData(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $set = ProductSet::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->patchJson("/product-sets/id:{$set->getKey()}", [
                'slug_suffix' => 'test-edit',
                'slug_override' => false,
                'parent_id' => null,
            ])
            ->assertOk();

        $this->assertDatabaseHas('product_sets', [
            'slug' => 'test-edit',
            'parent_id' => null,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateParentSlug(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $parent = ProductSet::factory()->create([
            'slug' => 'parent',
            'public' => true,
            'public_parent' => false,
        ]);

        $child = ProductSet::factory()->create([
            'parent_id' => $parent->getKey(),
            'slug' => 'parent-child',
            'public' => true,
            'public_parent' => false,
        ]);

        $grandchild = ProductSet::factory()->create([
            'parent_id' => $child->getKey(),
            'slug' => 'parent-child-grandchild',
            'public_parent' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->patchJson('/product-sets/id:' . $parent->getKey(), [
                'public' => true,
                'parent_id' => null,
                'slug_suffix' => 'new',
                'slug_override' => false,
                'children_ids' => [
                    $child->getKey(),
                ],
            ])
            ->assertOk();

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
    public function testUpdateWithSeo(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        $set = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $this
            ->actingAs($this->{$user})
            ->patchJson("/product-sets/id:{$set->getKey()}", [
                'seo' => [
                    'published' => [$this->lang],
                    'translations' => [
                        $this->lang => [
                            'title' => 'seo title',
                            'description' => 'seo description',
                        ],
                    ],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => 'seo title',
            "description->{$this->lang}" => 'seo description',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithAttributes(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        /** @var ProductSet $set */
        $set = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $attrOne = Attribute::factory()->create();
        $attrTwo = Attribute::factory()->create();
        $attrThree = Attribute::factory()->create();

        $set->attributes()->sync($attrOne->getKey());

        $this
            ->actingAs($this->{$user})
            ->patchJson("/product-sets/id:{$set->getKey()}", [
                'attributes' => [
                    $attrTwo->getKey(),
                    $attrThree->getKey(),
                ],
            ])
            ->assertOk();

        $this->assertFalse($set->attributes->contains($attrOne));
        $this->assertTrue($set->attributes->contains($attrTwo));
        $this->assertTrue($set->attributes->contains($attrThree));

        Event::assertDispatched(ProductSetUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithEmptyAttributes(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        /** @var ProductSet $set */
        $set = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $attrOne = Attribute::factory()->create();
        $set->attributes()->sync($attrOne->getKey());

        $this
            ->actingAs($this->{$user})
            ->patchJson("/product-sets/id:{$set->getKey()}", [
                'attributes' => [],
            ])->assertOk();

        $this->assertFalse($set->attributes->contains($attrOne));

        Event::assertDispatched(ProductSetUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateChangeParent(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $parent = ProductSet::factory()->create([
            'slug' => 'parent',
            'public' => true,
            'public_parent' => false,
        ]);

        $set = ProductSet::factory()->create([
            'parent_id' => $parent->getKey(),
            'slug' => 'child',
            'public' => true,
            'public_parent' => false,
        ]);

        $newParent = ProductSet::factory()->create([
            'slug' => 'new-parent',
            'public' => true,
            'public_parent' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->patchJson('/product-sets/id:' . $set->getKey(), [
                'parent_id' => $newParent->getKey(),
            ])
            ->assertOk()
            ->assertJsonFragment([
                'id' => $set->getKey(),
                'slug' => 'child',
            ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $set->getKey(),
            'slug' => 'child',
        ]);

        Event::assertDispatched(ProductSetUpdated::class);
    }
}
