<?php

namespace Tests\Feature;

use Domain\Price\Enums\ProductPriceType;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
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
        $this->productRepository->setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(13, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(3, $this->currency->value))],
        ]);
        $this->productRepository->setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(11, Currency::GBP->toCurrencyInstance()))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(1, Currency::GBP->toCurrencyInstance()))],
        ]);
        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(12, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(2, $this->currency->value))],
        ]);
        $this->productRepository->setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(12, Currency::GBP->toCurrencyInstance()))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(2, Currency::GBP->toCurrencyInstance()))],
        ]);
        $product3 = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product3->getKey(), [
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(11, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(1, $this->currency->value))],
        ]);
        $this->productRepository->setProductPrices($product3->getKey(), [
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(13, Currency::GBP->toCurrencyInstance()))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(3, Currency::GBP->toCurrencyInstance()))],
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
            ->json('GET', '/products', ['sort' => 'price:GBP:asc']);

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
            ->json('GET', '/products', ['sort' => 'price:GBP:desc']);

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

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testIndexSortPriceRoundingCheck(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(13, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(13, $this->currency->value))],
        ]);
        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $this->productRepository->setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of('7.09', $this->currency->value))],
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of('7.09', $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of('7.09', $this->currency->value))],
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['sort' => 'price:asc', 'price' => ['currency' => $this->currency->value, 'max' => '13']])
            ->assertOk()
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $product2->id,
                        'name' => $product2->name,
                    ],
                    1 => [
                        'id' => $product1->id,
                        'name' => $product1->name,
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(20);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSortByName(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $product1 = Product::factory()->create([
            'public' => true,
        ]);
        $product1->setLocale($this->lang)->fill([
            'name' => 'B product',
        ]);
        $product1->save();
        $product2 = Product::factory()->create([
            'public' => true,
        ]);
        $product2->setLocale($this->lang)->fill([
            'name' => 'C product',
        ]);
        $product2->save();
        $product3 = Product::factory()->create([
            'public' => true,
        ]);
        $product3->setLocale($this->lang)->fill([
            'name' => 'A product',
        ]);
        $product3->save();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['sort' => 'name:asc'])
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $product3->id,
                        'name' => $product3->name,
                    ],
                    1 => [
                        'id' => $product1->id,
                        'name' => $product1->name,
                    ],
                    2 => [
                        'id' => $product2->id,
                        'name' => $product2->name,
                    ],
                ],
            ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['sort' => 'name:desc'])
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $product2->id,
                        'name' => $product2->name,
                    ],
                    1 => [
                        'id' => $product1->id,
                        'name' => $product1->name,
                    ],
                    2 => [
                        'id' => $product3->id,
                        'name' => $product3->name,
                    ],
                ],
            ]);
    }
}
