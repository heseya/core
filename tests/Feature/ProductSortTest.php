<?php

namespace Tests\Feature;

use App\Dtos\PriceDto;
use App\Enums\Product\ProductPriceType;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class ProductSortTest extends TestCase
{
    private Currency $currency;

    private ProductRepositoryContract $productRepository;

    /**
     * @throws UnknownCurrencyException
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::DEFAULT;

        $this->productRepository = App::make(ProductRepositoryContract::class);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testIndexSortPrice(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository::setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MAX->value => [new PriceDto(Money::of(13, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [new PriceDto(Money::of(3, $this->currency->value))],
        ]);
        $this->productRepository::setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MAX->value => [new PriceDto(Money::of(11, Currency::EUR->toCurrencyInstance()))],
            ProductPriceType::PRICE_MIN->value => [new PriceDto(Money::of(1, Currency::EUR->toCurrencyInstance()))],
        ]);
        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository::setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MAX->value => [new PriceDto(Money::of(12, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [new PriceDto(Money::of(2, $this->currency->value))],
        ]);
        $this->productRepository::setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MAX->value => [new PriceDto(Money::of(12, Currency::EUR->toCurrencyInstance()))],
            ProductPriceType::PRICE_MIN->value => [new PriceDto(Money::of(2, Currency::EUR->toCurrencyInstance()))],
        ]);
        $product3 = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository::setProductPrices($product3->getKey(), [
            ProductPriceType::PRICE_MAX->value => [new PriceDto(Money::of(11, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [new PriceDto(Money::of(1, $this->currency->value))],
        ]);
        $this->productRepository::setProductPrices($product3->getKey(), [
            ProductPriceType::PRICE_MAX->value => [new PriceDto(Money::of(13, Currency::EUR->toCurrencyInstance()))],
            ProductPriceType::PRICE_MIN->value => [new PriceDto(Money::of(3, Currency::EUR->toCurrencyInstance()))],
        ]);

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['sort' => 'price:asc']);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $product3->id,
                        'name' => $product3->name,
                    ],
                    1 => [
                        'id' => $product2->id,
                        'name' => $product2->name,
                    ],
                    2 => [
                        'id' => $product1->id,
                        'name' => $product1->name,
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(20);

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['sort' => 'price:desc']);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $product1->id,
                        'name' => $product1->name,
                    ],
                    1 => [
                        'id' => $product2->id,
                        'name' => $product2->name,
                    ],
                    2 => [
                        'id' => $product3->id,
                        'name' => $product3->name,
                    ],
                ],
            ]);

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['sort' => 'price:asc', 'price' => ['currency' => Currency::EUR->value]]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $product1->id,
                        'name' => $product1->name,
                    ],
                    1 => [
                        'id' => $product2->id,
                        'name' => $product2->name,
                    ],
                    2 => [
                        'id' => $product3->id,
                        'name' => $product3->name,
                    ],
                ],
            ]);

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['sort' => 'price:desc', 'price' => ['currency' => Currency::EUR->value]]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $product3->id,
                        'name' => $product3->name,
                    ],
                    1 => [
                        'id' => $product2->id,
                        'name' => $product2->name,
                    ],
                    2 => [
                        'id' => $product1->id,
                        'name' => $product1->name,
                    ],
                ],
            ]);
    }
}
