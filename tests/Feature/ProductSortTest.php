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
use Domain\SalesChannel\SalesChannelRepository;
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

        $defaultSalesChannel = app(SalesChannelRepository::class)->getDefault();

        $product1 = Product::factory()->create();
        $defaultSalesChannel->products()->attach($product1);
        $this->productRepository->setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(13, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(3, $this->currency->value))],
        ]);
        $this->productRepository->setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(11, Currency::EUR->toCurrencyInstance()))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(1, Currency::EUR->toCurrencyInstance()))],
        ]);
        $product2 = Product::factory()->create();
        $defaultSalesChannel->products()->attach($product2);
        $this->productRepository->setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(12, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(2, $this->currency->value))],
        ]);
        $this->productRepository->setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(12, Currency::EUR->toCurrencyInstance()))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(2, Currency::EUR->toCurrencyInstance()))],
        ]);
        $product3 = Product::factory()->create();
        $defaultSalesChannel->products()->attach($product3);
        $this->productRepository->setProductPrices($product3->getKey(), [
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(11, $this->currency->value))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(1, $this->currency->value))],
        ]);
        $this->productRepository->setProductPrices($product3->getKey(), [
            ProductPriceType::PRICE_MAX->value => [PriceDto::from(Money::of(13, Currency::EUR->toCurrencyInstance()))],
            ProductPriceType::PRICE_MIN->value => [PriceDto::from(Money::of(3, Currency::EUR->toCurrencyInstance()))],
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

        $this->assertQueryCountLessThan(22);

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
            ->json('GET', '/products', ['sort' => 'price:EUR:asc']);

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
            ->json('GET', '/products', ['sort' => 'price:EUR:desc']);

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
     */
    public function testIndexSortByName(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');

        $defaultSalesChannel = app(SalesChannelRepository::class)->getDefault();

        $product1 = Product::factory()->create();
        $product1->setLocale($this->lang)->fill([
            'name' => 'B product',
        ]);
        $product1->save();
        $product2 = Product::factory()->create();
        $product2->setLocale($this->lang)->fill([
            'name' => 'C product',
        ]);
        $product2->save();
        $product3 = Product::factory()->create();
        $product3->setLocale($this->lang)->fill([
            'name' => 'A product',
        ]);
        $product3->save();

        $defaultSalesChannel->products()->sync([
            $product1->getKey(),
            $product2->getKey(),
            $product3->getKey(),
        ]);

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
