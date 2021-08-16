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
            'order' => 50,
        ]);

        $response = $this->deleteJson('/product-sets/id:' . $newSet->getKey());
        $response->assertUnauthorized();
    }

    public function testDelete(): void
    {
        $newSet = ProductSet::factory()->create([
            'public' => true,
            'order' => 60,
        ]);

        $this->assertDatabaseHas('product_sets', $newSet->toArray());

        $response = $this->actingAs($this->user)->deleteJson(
            '/product-sets/id:' . $newSet->getKey(),
        );
        $response->assertNoContent();
        $this->assertDeleted($newSet);
    }

    public function testDeleteWithProducts(): void
    {
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
}
