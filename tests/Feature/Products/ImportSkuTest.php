<?php

namespace Tests\Feature\Products;

use App\Models\Item;
use App\Models\Product;
use App\Models\ProductAttribute;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Tests\TestCase;

class ImportSkuTest extends TestCase
{
    public Product $product;

    public function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create([
            'public' => true,
        ]);

        $item = Item::factory()->create([
            'sku' => 'SKU000123',
        ]);

        $this->product->items()->attach([$item->getKey() => ['required_quantity' => 1]]);
    }

    public function testNoSkuAttribute(): void
    {
        $this->artisan('products:import-sku')
            ->expectsOutput('No SKU attribute found.');
    }

    public function testImportSkuNoProducts(): void
    {
        $attribute = Attribute::factory()->create([
            'slug' => 'sku',
            'name' => 'SKU',
            'type' => AttributeType::SINGLE_OPTION->value,
        ]);

        $option = $attribute->options()->create([
            'name' => 'SKU000321',
            'index' => 0,
        ]);

        $productAttribute = ProductAttribute::create([
            'attribute_id' => $attribute->getKey(),
            'product_id' => $this->product->getKey(),
        ]);
        $productAttribute->options()->attach($option);

        $this
            ->artisan('products:import-sku')
            ->expectsOutput('No products found.');
    }

    public function testImportSku(): void
    {
        $productWithSku = Product::factory()->create([
            'public' => true,
        ]);
        $item = Item::factory()->create([
            'sku' => 'SKU000321',
        ]);
        $productWithSku->items()->attach([$item->getKey() => ['required_quantity' => 1]]);

        $attribute = Attribute::factory()->create([
            'slug' => 'sku',
            'name' => 'SKU',
            'type' => AttributeType::SINGLE_OPTION->value,
        ]);

        $option = $attribute->options()->create([
            'name' => 'SKU000321',
            'index' => 0,
        ]);

        $productAttribute = ProductAttribute::create([
            'attribute_id' => $attribute->getKey(),
            'product_id' => $productWithSku->getKey(),
        ]);
        $productAttribute->options()->attach($option);

        $this
            ->artisan('products:import-sku')
            ->expectsOutputToContain('1/1')
            ->expectsOutput('Done.');

        $this->assertDatabaseHas('attribute_options', [
            'attribute_id' => $attribute->getKey(),
            "name->{$this->lang}" => 'SKU000123',
        ]);

        $this->assertDatabaseHas('product_attribute', [
            'attribute_id' => $attribute->getKey(),
            'product_id' => $this->product->getKey(),
        ]);

        $this->assertDatabaseCount('product_attribute_attribute_option', 2);
    }
}
