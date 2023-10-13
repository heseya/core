<?php

namespace Tests\Feature\Products;

use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Product;
use Tests\TestCase;

class ProductPriceTest extends TestCase
{
    public Product $product;

//    public function setUp(): void
//    {
//        parent::setUp();
//
//        $this->product = Product::factory()->create([
//            'name' => 'Searched product',
//            'public' => true,
//            'description_html' => 'Lorem ipsum',
//            'description_short' => 'short',
//            'price' => 1000,
//            'price_min_initial' => 1000,
//            'price_max_initial' => 1000,
//        ]);
//    }

    public function testUpdateProductPrices(): void
    {
        $this->markTestSkipped();

        Discount::factory()->create([
            'code' => null,
            'type' => DiscountType::AMOUNT,
            'value' => 100.0,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        Discount::factory()->create([
            'code' => null,
            'type' => DiscountType::AMOUNT,
            'value' => 150.0,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
        ]);

        Discount::factory()->create([
            'code' => null,
            'type' => DiscountType::AMOUNT,
            'value' => 75.0,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => false,
        ]);

        $sale = Discount::factory()->create([
            'code' => null,
            'type' => DiscountType::AMOUNT,
            'value' => 30.0,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);
        $sale->products()->sync($this->product->getKey());

        $this->artisan('products:update-prices', ['id' => $this->product->getKey()]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'price' => 1000,
            'price_min' => 820, // 1000 - 150 - 30
            'price_max' => 820, // 1000 - 150 - 30
            'price_min_initial' => 1000,
            'price_max_initial' => 1000,
        ]);
    }
}
