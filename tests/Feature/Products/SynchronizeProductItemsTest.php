<?php

namespace Tests\Feature\Products;

use App\Models\Item;
use App\Models\Product;
use Domain\Metadata\Enums\MetadataType;
use Tests\TestCase;

class SynchronizeProductItemsTest extends TestCase
{
    public Product $product;

    public function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create([
            'public' => true,
        ]);
    }

    public function testSyncNoMetadataProducts(): void
    {
        $this
            ->artisan('products:sync-items external_id')
            ->expectsOutput('No products found.');
    }

    public function testSyncProductItems(): void
    {
        $product = Product::factory()->create([
            'public' => true,
        ]);

        $product->metadata()->create([
            'name' => 'external_id',
            'value' => 'TEST654321',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $item = Item::factory()->create([
            'sku' => 'TEST654321',
            'name' => $product->name,
        ]);

        $this->product->metadata()->create([
            'name' => 'external_id',
            'value' => 'TEST123456',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->artisan('products:sync-items external_id')
            ->expectsOutputToContain('2/2')
            ->expectsOutput('Done.');

        $this->assertDatabaseHas('items', [
            'sku' => 'TEST123456',
            'name' => $this->product->name,
        ]);

        $newItem = Item::query()->where('sku', 'TEST123456')->first();

        $this->assertDatabaseHas('item_product', [
            'product_id' => $this->product->getKey(),
            'item_id' => $newItem->getKey(),
        ]);

        $this->assertDatabaseHas('item_product', [
            'product_id' => $product->getKey(),
            'item_id' => $item->getKey(),
        ]);
    }
}
