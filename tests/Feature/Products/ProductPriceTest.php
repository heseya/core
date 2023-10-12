<?php

namespace Tests\Feature\Products;

use App\Enums\DiscountTargetType;
use App\Models\Discount;
use App\Models\Product;
use Domain\Currency\Currency;
use Domain\Price\Enums\ProductPriceType;
use Tests\TestCase;

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

        $this->product->pricesBase()->where('currency', '=', Currency::DEFAULT->value)->update(['value' => '100000']);
        $this->product->pricesMinInitial()->where('currency', '=', Currency::DEFAULT->value)->update(['value' => '100000']);
        $this->product->pricesMin()->where('currency', '=', Currency::DEFAULT->value)->update(['value' => '100000']);
        $this->product->pricesMaxInitial()->where('currency', '=', Currency::DEFAULT->value)->update(['value' => '100000']);
        $this->product->pricesMax()->where('currency', '=', Currency::DEFAULT->value)->update(['value' => '100000']);
    }

    public function testUpdateProductPrices(): void
    {
        // TODO needs fix!!!
        self::markTestSkipped('For some reason, after change to prices, each time price_min and price_max have different values');
        $d1 = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);
        $d1->amounts()->where('currency', '=', Currency::DEFAULT->value)->update([
            'value' => '10000',
            'price_type' => 'amount',
            'currency' => Currency::DEFAULT->value,
        ]);

        $d2 = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
        ]);
        $d2->amounts()->where('currency', '=', Currency::DEFAULT->value)->update([
            'value' => '15000',
            'price_type' => 'amount',
            'currency' => Currency::DEFAULT->value,
        ]);

        $d3 = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => false,
        ]);
        $d3->amounts()->where('currency', '=', Currency::DEFAULT->value)->update([
            'value' => '7500',
            'price_type' => 'amount',
            'currency' => Currency::DEFAULT->value,
        ]);

        $sale = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);
        $sale->amounts()->where('currency', '=', Currency::DEFAULT->value)->update([
            'value' => '3000',
            'price_type' => 'amount',
            'currency' => Currency::DEFAULT->value,
        ]);
        $sale->products()->sync($this->product->getKey());

        $this->artisan('products:update-prices', ['id' => $this->product->getKey()]);

        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'model_type' => $this->product->getMorphClass(),
            'price_type' => ProductPriceType::PRICE_BASE,
            'value' => '100000',
            'currency' => Currency::DEFAULT->value,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'model_type' => $this->product->getMorphClass(),
            'price_type' => ProductPriceType::PRICE_MIN,
            'value' => '82000', // 1000 - 150 - 30
            'currency' => Currency::DEFAULT->value,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'model_type' => $this->product->getMorphClass(),
            'price_type' => ProductPriceType::PRICE_MAX,
            'value' => '82000', // 1000 - 150 - 30
            'currency' => Currency::DEFAULT->value,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'model_type' => $this->product->getMorphClass(),
            'price_type' => ProductPriceType::PRICE_MAX_INITIAL,
            'value' => '100000',
            'currency' => Currency::DEFAULT->value,
        ]);
        $this->assertDatabaseHas('prices', [
            'model_id' => $this->product->getKey(),
            'model_type' => $this->product->getMorphClass(),
            'price_type' => ProductPriceType::PRICE_MIN_INITIAL,
            'price_max_initial' => '100000',
            'currency' => Currency::DEFAULT->value,
        ]);
    }
}
