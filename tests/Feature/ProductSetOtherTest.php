<?php

namespace Tests\Feature;

use App\Models\ProductSet;
use App\Models\Product;
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

    public function testDeleteWithRelations(): void
    {
        $set = ProductSet::factory()->create([
            'public' => true,
            'order' => 60,
        ]);

        $set->products()->save(Product::factory()->make());

        $response = $this->actingAs($this->user)->delete(
            '/product-sets/id:' . $set->getKey(),
        );
        $response->assertStatus(400);
        $this->assertDatabaseHas('product_sets', $set->toArray());
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
}
