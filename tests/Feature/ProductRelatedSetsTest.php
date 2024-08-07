<?php

use App\Models\Product;
use App\Models\ProductSet;
use Tests\TestCase;

class ProductRelatedSetsTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testShowRelatedSets($user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        /** @var Product $product */
        $product = Product::factory()->create([
            'public' => true,
        ]);

        $set1 = ProductSet::factory()->create([
            'public' => true,
        ]);
        $set2 = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product->relatedSets()->sync([$set1->getKey(), $set2->getKey()]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/products/' . $product->slug)
            ->assertOk()
            ->assertJsonFragment(['related_sets' => [
                [
                    'id' => $set1->getKey(),
                    'name' => $set1->name,
                    'slug' => $set1->slug,
                    'slug_suffix' => $set1->slugSuffix,
                    'slug_override' => $set1->slugOverride,
                    'public' => $set1->public,
                    'visible' => $set1->public_parent && $set1->public,
                    'parent_id' => $set1->parent_id,
                    'children_ids' => [],
                    'cover' => null,
                    'metadata' => [],
                ],
                [
                    'id' => $set2->getKey(),
                    'name' => $set2->name,
                    'slug' => $set2->slug,
                    'slug_suffix' => $set2->slugSuffix,
                    'slug_override' => $set2->slugOverride,
                    'public' => $set2->public,
                    'visible' => $set2->public_parent && $set2->public,
                    'parent_id' => $set2->parent_id,
                    'children_ids' => [],
                    'cover' => null,
                    'metadata' => [],
                ],
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowPrivateRelatedSetsNoPermission($user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        /** @var Product $product */
        $product = Product::factory()->create([
            'public' => true,
        ]);

        $set1 = ProductSet::factory()->create([
            'public' => true,
        ]);
        $set2 = ProductSet::factory()->create([
            'public' => false,
        ]);

        $product->relatedSets()->sync([$set1->getKey(), $set2->getKey()]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/products/' . $product->slug)
            ->assertOk()
            ->assertJsonFragment(['related_sets' => [
                [
                    'id' => $set1->getKey(),
                    'name' => $set1->name,
                    'slug' => $set1->slug,
                    'slug_suffix' => $set1->slugSuffix,
                    'slug_override' => $set1->slugOverride,
                    'public' => $set1->public,
                    'visible' => $set1->public_parent && $set1->public,
                    'parent_id' => $set1->parent_id,
                    'children_ids' => [],
                    'cover' => null,
                    'metadata' => [],
                ],
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowPrivateRelatedSetsWithPermission($user): void
    {
        $this->{$user}->givePermissionTo(['products.show_details', 'product_sets.show_hidden']);

        /** @var Product $product */
        $product = Product::factory()->create([
            'public' => true,
        ]);

        $set1 = ProductSet::factory()->create([
            'public' => true,
        ]);
        $set2 = ProductSet::factory()->create([
            'public' => false,
        ]);

        $product->relatedSets()->sync([$set1->getKey(), $set2->getKey()]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/products/' . $product->slug)
            ->assertOk()
            ->assertJsonFragment(['related_sets' => [
                [
                    'id' => $set1->getKey(),
                    'name' => $set1->name,
                    'slug' => $set1->slug,
                    'slug_suffix' => $set1->slugSuffix,
                    'slug_override' => $set1->slugOverride,
                    'public' => $set1->public,
                    'visible' => $set1->public_parent && $set1->public,
                    'parent_id' => $set1->parent_id,
                    'children_ids' => [],
                    'cover' => null,
                    'metadata' => [],
                ],
                [
                    'id' => $set2->getKey(),
                    'name' => $set2->name,
                    'slug' => $set2->slug,
                    'slug_suffix' => $set2->slugSuffix,
                    'slug_override' => $set2->slugOverride,
                    'public' => $set2->public,
                    'visible' => $set2->public_parent && $set2->public,
                    'parent_id' => $set2->parent_id,
                    'children_ids' => [],
                    'cover' => null,
                    'metadata' => [],
                ],
            ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithRelatedSets($user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $response = $this->actingAs($this->{$user})->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 150,
            'public' => false,
            'shipping_digital' => false,
            'related_sets' => [
                $set1->getKey(),
                $set2->getKey(),
            ],
        ]);

        $response->assertCreated();
        $product = $response->getData()->data;

        $this->assertDatabaseHas('related_product_sets', [
            'product_id' => $product->id,
            'product_set_id' => $set1->getKey(),
        ]);

        $this->assertDatabaseHas('related_product_sets', [
            'product_id' => $product->id,
            'product_set_id' => $set2->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateChangeRelatedSets($user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var Product $product */
        $product = Product::factory()->create();

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();
        $set3 = ProductSet::factory()->create();

        $product->relatedSets()->sync([$set1->getKey(), $set2->getKey()]);

        $response = $this->actingAs($this->{$user})->patchJson('/products/id:' . $product->getKey(), [
            'related_sets' => [
                $set2->getKey(),
                $set3->getKey(),
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('related_product_sets', [
            'product_id' => $product->getKey(),
            'product_set_id' => $set2->getKey(),
        ]);

        $this->assertDatabaseHas('related_product_sets', [
            'product_id' => $product->getKey(),
            'product_set_id' => $set3->getKey(),
        ]);

        $this->assertDatabaseMissing('related_product_sets', [
            'product_id' => $product->getKey(),
            'product_set_id' => $set1->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateDeleteRelatedSets($user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var Product $product */
        $product = Product::factory()->create();

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $product->relatedSets()->sync([$set1->getKey(), $set2->getKey()]);

        $response = $this->actingAs($this->{$user})->patchJson('/products/id:' . $product->getKey(), [
            'related_sets' => [],
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('related_product_sets', [
            'product_id' => $product->getKey(),
        ]);
    }
}
