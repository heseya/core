<?php

namespace Tests\Feature\Products;

use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Domain\Price\Enums\ProductPriceType;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class ProductPriceTest extends TestCase
{
    public Product $product;

    public function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create([
            'name' => 'Searched product',
            'public' => true,
            'description_html' => 'Lorem ipsum',
            'description_short' => 'short',
        ]);

        app(ProductRepository::class)->setProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(amount: 1000),
        ]);
    }

    public function testUpdateProductPrices(): void
    {
        $this->markTestSkipped();

        Discount::factory()->create([
            'code' => null,
            'value' => 100.0,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        Discount::factory()->create([
            'code' => null,
            'value' => 150.0,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
        ]);

        Discount::factory()->create([
            'code' => null,
            'value' => 75.0,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => false,
        ]);

        $sale = Discount::factory()->create([
            'code' => null,
            'value' => 30.0,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);
        $sale->products()->sync($this->product->getKey());

        $this->artisan('products:update-prices', ['id' => $this->product->getKey()]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
        ]);

        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_BASE,
            'value' => 1000,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MIN,
            'value' => 820,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MAX,
            'value' => 820,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MAX_INITIAL,
            'value' => 1000,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'price_type' => ProductPriceType::PRICE_MIN_INITIAL,
            'value' => 1000,
        ]);
    }
}
