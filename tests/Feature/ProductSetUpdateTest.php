<?php

namespace Tests\Feature;

use App\Events\ProductSetUpdated;
use App\Models\Attribute;
use App\Models\ProductSet;
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

        $newSet = ProductSet::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->patchJson('/product-sets/id:' . $newSet->getKey(), [
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
            ->assertJson(['data' => [
                'name' => 'Test Edit',
                'parent' => null,
                'children_ids' => [],
                'slug' => 'test-edit',
                'slug_suffix' => 'test-edit',
                'slug_override' => false,
            ]]);

        $this->assertDatabaseHas('product_sets', [
            "name->{$this->lang}" => 'Test Edit',
            'parent_id' => null,
            'slug' => 'test-edit',
        ]);

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
    public function testUpdateWithoutAttributes(string $user): void
    {
        $this->{$user}->givePermissionTo('product_sets.edit');

        Event::fake([ProductSetUpdated::class]);

        $newSet = ProductSet::factory()->create([
            'public' => false,
            'order' => 40,
        ]);

        $attrOne = Attribute::factory()->create();

        $newSet->attributes()->sync($attrOne->getKey());

        $parentId = [
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->{$user})->patchJson(
            '/product-sets/id:' . $newSet->getKey(),
            $parentId + [
                'children_ids' => [],
                'slug_suffix' => 'test-edit',
                'slug_override' => false,
            ],
        );

        $response
            ->assertOk()
            ->assertJson(['data' => [
                'parent' => null,
                'children_ids' => [],
                'slug' => 'test-edit',
                'slug_suffix' => 'test-edit',
                'slug_override' => false,
            ]]);

        $this->assertDatabaseHas('product_sets', $parentId + [
            'slug' => 'test-edit',
        ]);

        $this->assertTrue($newSet->attributes->contains($attrOne));

        Event::assertDispatched(ProductSetUpdated::class);
    }
}
