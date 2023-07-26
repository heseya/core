<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\AttributeOption;
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
        $this->{$user}->givePermissionTo('product_sets.show_details');

        $response = $this->actingAs($this->{$user})
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
                'parent' => $this->set->parent,
                'children_ids' => [
                    $this->childSet->getKey(),
                ],
                'seo' => [
                    'title' => $this->set->seo->title,
                    'description' => $this->set->seo->description,
                ],
            ],
            ])
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWrongId($user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show_details');

        $this->actingAs($this->{$user})
            ->getJson('/product-sets/id:its-not-uuid')
            ->assertNotFound();

        $this->actingAs($this->{$user})
            ->getJson('/product-sets/id:' . $this->set->getKey() . $this->set->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowHiddenUnauthorized($user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show_details');

        $this
            ->actingAs($this->{$user})
            ->getJson('/product-sets/id:' . $this->privateSet->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowHidden($user): void
    {
        $this->{$user}->givePermissionTo(['product_sets.show_details', 'product_sets.show_hidden']);

        $response = $this->actingAs($this->{$user})
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
                'parent' => null,
                'children_ids' => [],
                'seo' => [
                    'title' => $this->privateSet->seo->title,
                    'description' => $this->privateSet->seo->description,
                ],
            ],
            ])
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowSlugUnauthorized($user): void
    {
        $response = $this->actingAs($this->{$user})
            ->getJson('/product-sets/' . $this->set->slug);
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowSlug($user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show_details');

        $response = $this->actingAs($this->{$user})
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
                'parent' => $this->set->parent,
                'children_ids' => [
                    $this->childSet->getKey(),
                ],
                'seo' => [
                    'title' => $this->set->seo->title,
                    'description' => $this->set->seo->description,
                ],
            ],
            ])
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWrongSlug($user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show_details');

        $this
            ->actingAs($this->{$user})
            ->getJson('/product-sets/its_wrong_slug')
            ->assertNotFound();

        $this
            ->actingAs($this->{$user})
            ->getJson('/product-sets/' . $this->set->slug . '_' . $this->set->slug)
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowSlugHiddenUnauthorized($user): void
    {
        $this->{$user}->givePermissionTo('product_sets.show_details');

        $response = $this->actingAs($this->{$user})
            ->getJson('/product-sets/' . $this->privateSet->slug);
        $response->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowSlugHidden($user): void
    {
        $this->{$user}->givePermissionTo(['product_sets.show_details', 'product_sets.show_hidden']);

        $response = $this->actingAs($this->{$user})->getJson('/product-sets/' . $this->privateSet->slug);
        $response
            ->assertOk()
            ->assertJson(['data' => [
                'id' => $this->privateSet->getKey(),
                'name' => $this->privateSet->name,
                'slug' => $this->privateSet->slug,
                'slug_override' => false,
                'public' => $this->privateSet->public,
                'visible' => $this->privateSet->public && $this->privateSet->public_parent,
                'parent' => null,
                'children_ids' => [],
                'seo' => [
                    'title' => $this->privateSet->seo->title,
                    'description' => $this->privateSet->seo->description,
                ],
            ],
            ])
            ->assertJsonStructure([
                'data' => $this->expected_structure,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductSetHasAttributes($user): void
    {
        $this->{$user}->givePermissionTo(['product_sets.show_details', 'product_sets.show_hidden']);

        $firstAttr = Attribute::factory()->create([
            'name' => 'test',
            'description' => 'test',
            'type' => 'number',
            'global' => false,
        ]);
        $secondAttr = Attribute::factory()->create([
            'name' => 'test2',
            'description' => 'test2',
            'type' => 'number',
            'global' => false,
        ]);

        $this->set->attributes()->attach([
            $firstAttr->getKey(),
            $secondAttr->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})
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
                'parent' => $this->set->parent,
                'children_ids' => [
                    $this->childSet->getKey(),
                ],
                'seo' => [
                    'title' => $this->set->seo->title,
                    'description' => $this->set->seo->description,
                ],
            ],
            ])
            ->assertJsonStructure([
                'data' => array_merge($this->expected_structure, ['attributes']),
            ])
            ->assertJsonCount(2, 'data.attributes');
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductSetHasAttributesWithOptions($user): void
    {
        $this->{$user}->givePermissionTo(['product_sets.show_details', 'product_sets.show_hidden']);

        $attribute = Attribute::factory()->create([
            'name' => 'test',
            'description' => 'test',
            'type' => 'number',
            'global' => false,
        ]);

        $attributeOption = AttributeOption::factory()->create([
            'index' => 1,
            'value_number' => 100,
            'attribute_id' => $attribute->getKey(),
        ]);

        $this->set->attributes()->attach([
            $attribute->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})
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
                'parent' => $this->set->parent,
                'children_ids' => [
                    $this->childSet->getKey(),
                ],
                'seo' => [
                    'title' => $this->set->seo->title,
                    'description' => $this->set->seo->description,
                ],
                'attributes' => [
                    [
                        'id' => $attribute->getKey(),
                        'name' => $attribute->name,
                        'slug' => $attribute->slug,
                        'description' => $attribute->description,
                        'min' => 100,
                        'max' => 100,
                        'type' => $attribute->type->value,
                        'global' => $attribute->global,
                        'sortable' => $attribute->sortable,
                    ],
                ],
            ],
            ])->assertJsonMissing([
                'options' => [
                    [
                        'id' => $attributeOption->getKey(),
                        'name' => $attributeOption->name,
                        'index' => $attributeOption->index,
                        'value_number' => $attributeOption->value_number,
                        'value_date' => $attributeOption->value_date,
                        'attribute_id' => $attribute->getKey(),
                    ],
                ],
            ]);
    }
}
