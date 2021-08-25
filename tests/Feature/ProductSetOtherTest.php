<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductSet;
use Tests\TestCase;

class ProductSetOtherTest extends TestCase
{
    public function testDeleteUnauthorized(): void
    {
        $newSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $response = $this->deleteJson('/product-sets/id:' . $newSet->getKey());
        $response->assertForbidden();
    }

    public function testDelete(): void
    {
        $this->user->givePermissionTo('product_sets.remove');

        $newSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $response = $this->actingAs($this->user)->deleteJson(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertDeleted($newSet);
    }

    public function testDeleteAsBrand(): void
    {
        $this->user->givePermissionTo('product_sets.remove');

        $newSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product = Product::factory()->create([
           'brand_id' => $newSet->getKey(),
        ]);

        $response = $this->actingAs($this->user)->deleteJson(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertDeleted($newSet);

        $this->assertDatabaseHas('products', [
            $product->getKeyName() => $product->getKey(),
            'brand_id' => null,
        ]);
    }

    public function testDeleteAsCategory(): void
    {
        $this->user->givePermissionTo('product_sets.remove');

        $newSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product = Product::factory()->create([
            'category_id' => $newSet->getKey(),
        ]);

        $response = $this->actingAs($this->user)->deleteJson(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertDeleted($newSet);

        $this->assertDatabaseHas('products', [
            $product->getKeyName() => $product->getKey(),
            'category_id' => null,
        ]);
    }

    public function testDeleteWithProducts(): void
    {
        $this->user->givePermissionTo('product_sets.remove');

        $newSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        $newSet->products()->sync([
            $product1->getKey(),
            $product2->getKey(),
            $product3->getKey(),
        ]);

        $response = $this->actingAs($this->user)->delete(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertDeleted($newSet);

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $product1->getKey(),
            'product_set_id' => $newSet->getKey(),
        ]);

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $product2->getKey(),
            'product_set_id' => $newSet->getKey(),
        ]);

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $product3->getKey(),
            'product_set_id' => $newSet->getKey(),
        ]);
    }

    public function testDeleteWithSubsets(): void
    {
        $this->user->givePermissionTo('product_sets.remove');

        $newSet = ProductSet::factory()->create([
            'public' => true,
        ]);

        $subset1 = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $newSet->getKey(),
        ]);

        $subset2 = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $newSet->getKey(),
        ]);

        $subset3 = ProductSet::factory()->create([
            'public' => true,
            'parent_id' => $newSet->getKey(),
        ]);

        $response = $this->actingAs($this->user)->delete(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertDeleted($newSet);
        $this->assertDeleted($subset1);
        $this->assertDeleted($subset2);
        $this->assertDeleted($subset3);
    }

    public function testReorderRoot(): void
    {
        $this->user->givePermissionTo('product_sets.edit');

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();
        $set3 = ProductSet::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/product-sets/reorder', [
            'product_sets' => [
                $set3->getKey(),
                $set2->getKey(),
                $set1->getKey(),
            ],
        ]);
        $response->assertNoContent();

        $this->assertDatabaseHas('product_sets', [
            'id' => $set3->getKey(),
            'order' => 0,
        ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $set2->getKey(),
            'order' => 1,
        ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $set1->getKey(),
            'order' => 2,
        ]);
    }

    public function testReorderChildren(): void
    {
        $this->user->givePermissionTo('product_sets.edit');

        $parent = ProductSet::factory()->create();
        $set1 = ProductSet::factory()->create([
            'parent_id' => $parent->getKey(),
        ]);
        $set2 = ProductSet::factory()->create([
            'parent_id' => $parent->getKey(),
        ]);
        $set3 = ProductSet::factory()->create([
            'parent_id' => $parent->getKey(),
        ]);

        $response = $this->actingAs($this->user)->postJson('/product-sets/reorder/id:' . $parent->getKey(), [
            'product_sets' => [
                $set3->getKey(),
                $set2->getKey(),
                $set1->getKey(),
            ],
        ]);
        $response->assertNoContent();

        $this->assertDatabaseHas('product_sets', [
            'id' => $set3->getKey(),
            'order' => 0,
        ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $set2->getKey(),
            'order' => 1,
        ]);

        $this->assertDatabaseHas('product_sets', [
            'id' => $set1->getKey(),
            'order' => 2,
        ]);
    }

    public function testAttachProducts(): void
    {
        $this->user->givePermissionTo('product_sets.edit');

        $set = ProductSet::factory()->create();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        $response = $this->actingAs($this->user)->postJson(
            '/product-sets/id:' . $set->getKey() . '/products',
            [
                'products' => [
                    $product1->getKey(),
                    $product2->getKey(),
                    $product3->getKey(),
                ],
            ],
        );

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $product1->getKey(),
            'product_set_id' => $set->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $product2->getKey(),
            'product_set_id' => $set->getKey(),
        ]);

        $this->assertDatabaseHas('product_set_product', [
            'product_id' => $product3->getKey(),
            'product_set_id' => $set->getKey(),
        ]);
    }

    public function testDetachProducts(): void
    {
        $this->user->givePermissionTo('product_sets.edit');

        $set = ProductSet::factory()->create();

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $product3 = Product::factory()->create();

        $set->products()->sync([
            $product1->getKey(),
            $product2->getKey(),
            $product3->getKey(),
        ]);

        $response = $this->actingAs($this->user)->postJson(
            '/product-sets/id:' . $set->getKey() . '/products',
            [
                'products' => [],
            ],
        );

        $response
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $product1->getKey(),
            'product_set_id' => $set->getKey(),
        ]);

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $product2->getKey(),
            'product_set_id' => $set->getKey(),
        ]);

        $this->assertDatabaseMissing('product_set_product', [
            'product_id' => $product3->getKey(),
            'product_set_id' => $set->getKey(),
        ]);
    }

    public function testShowProductsUnauthorized(): void
    {
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson(
            '/product-sets/id:' . $set->getKey() . '/products',
        );

        $response->assertForbidden();
    }

    public function testShowProducts(): void
    {
        $this->user->givePermissionTo('product_sets.show_details');

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $product2 = Product::factory()->create([
            'public' => false,
        ]);
        $productNotInSet = Product::factory()->create([
            'public' => true,
        ]);

        $set->products()->sync([
            $product1->getKey(),
            $product2->getKey(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/product-sets/id:' . $set->getKey() . '/products');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                [
                    'id' => $product1->getKey(),
                    'name' => $product1->name,
                    'slug' => $product1->slug,
                ]
            ]]);
    }

    public function testShowProductsHidden(): void
    {
        $this->user->givePermissionTo(['product_sets.show_details', 'product_sets.show_hidden']);

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $product2 = Product::factory()->create([
            'public' => false,
        ]);

        $set->products()->sync([
            $product1->getKey(),
            $product2->getKey(),
        ]);

        $response = $this->actingAs($this->user)->getJson(
            '/product-sets/id:' . $set->getKey() . '/products',
        );

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $product1->getKey(),
                'name' => $product1->name,
                'slug' => $product1->slug,
            ])
            ->assertJsonFragment([
                'id' => $product2->getKey(),
                'name' => $product2->name,
                'slug' => $product2->slug,
            ]);
    }
}
