<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Schema;
use App\Services\ProductService;
use App\Services\SchemaCrudService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class ProductServiceTest extends TestCase
{
    private ProductService $productService;

    private static Currency $currency = Currency::DEFAULT;
    private static int $price = 10;
    private Product $product;
    private SchemaCrudService $schemaCrudService;

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->productService = App::make(ProductService::class);
        $this->schemaCrudService = App::make(SchemaCrudService::class);

        // @var Product $product
        $this->product = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [PriceDto::fromMoney(Money::of(self::$price, self::$currency->value))],
        ]));
    }

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function testMinMaxPricesNoSchemas(): void
    {
        $this->assertEquals(
            [
                PriceDto::fromMoney(Money::of(self::$price, self::$currency->value)),
                PriceDto::fromMoney(Money::of(self::$price, self::$currency->value)),
            ],
            $this->productService->getMinMaxPrices($this->product),
        );
    }

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public static function schemaProvider(): array
    {
        $base = [
            $schemaPrice = 0 // schema hasn't price anymore,
        ];

        return [
            'required schema' => array_merge(
                $base,
                [
                    true,
                    PriceDto::fromMoney(Money::of(self::$price + $schemaPrice, self::$currency->value)),
                    PriceDto::fromMoney(Money::of(self::$price + $schemaPrice, self::$currency->value)),
                ],
            ),
        ];
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testMinMaxPricesSchema(
        float $schemaPrice,
        bool $required,
        PriceDto $min,
        PriceDto $max,
    ): void {
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'Test',
            'prices' => [PriceDto::from(Money::of($schemaPrice, self::$currency->value))],
            'required' => $required,
        ]));

        $this->product->schemas()->attach($schema->getKey());

        $this->product->refresh()->load('schemas');

        $calculated = $this->productService->getMinMaxPrices($this->product, self::$currency);

        $this->assertTrue($calculated[0]->value->getAmount()->isEqualTo($min->value->getAmount()));
        $this->assertTrue($calculated[1]->value->getAmount()->isEqualTo($max->value->getAmount()));
    }

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public static function selectSchemaProvider(): array
    {
        $base = [
            $schemaPrice = 0, //schema hasn't price anymore
            $optionPriceLowest = 5,
            $optionPriceMedium = 7,
            $optionPriceHighest = 10,
        ];

        return [
            'required' => array_merge($base, [
                true,
                [
                    PriceDto::fromMoney(Money::of(self::$price + $schemaPrice + $optionPriceLowest, self::$currency->value)),
                    PriceDto::fromMoney(Money::of(self::$price + $schemaPrice + $optionPriceHighest, self::$currency->value)),
                ],
            ]),
        ];
    }

    /**
     * @dataProvider selectSchemaProvider
     */
    public function testMinMaxPricesSelectSchema(
        float $schemaPrice,
        float $optionPriceLowest,
        float $optionPriceMedium,
        float $optionPriceHighest,
        bool $required,
        array $minmax,
    ): void {
        /** @var Schema $schema */
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'Test',
            'prices' => [PriceDto::from(Money::of($schemaPrice, self::$currency->value))],
            'required' => $required,
            'options' => [
                [
                    'name' => 'opt1',
                    'prices' => [PriceDto::from(Money::of($optionPriceLowest, self::$currency->value))],
                ],
                [
                    'name' => 'opt2',
                    'prices' => [PriceDto::from(Money::of($optionPriceMedium, self::$currency->value))],
                ],
                [
                    'name' => 'opt3',
                    'prices' => [PriceDto::from(Money::of($optionPriceHighest, self::$currency->value))],
                ],
            ]
        ]));

        $this->product->schemas()->attach($schema->getKey());
        $this->product->load('schemas');

        $this->assertEquals(
            $minmax,
            $this->productService->getMinMaxPrices($this->product),
        );
    }

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public static function multiplySchemaProvider(): array
    {
        $base = [
            $schemaPrice = 0, //schema hasn't price anymore
            $schemaMin = 2,
            $schemaMax = 5,
        ];

        return [
            'required' => array_merge($base, [
                true,
                [
                    PriceDto::fromMoney(Money::of(self::$price + $schemaPrice * $schemaMin, self::$currency->value)),
                    PriceDto::fromMoney(Money::of(self::$price + $schemaPrice * $schemaMax, self::$currency->value))
                ]
            ]),
        ];
    }

    /**
     * @dataProvider multiplySchemaProvider
     */
    public function testMinMaxPricesMultiplySchema(
        float $schemaPrice,
        float $schemaMin,
        float $schemaMax,
        bool $required,
        array $minmax,
    ): void {
        /** @var Schema $schema */
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'Test',
            'prices' => [PriceDto::from(Money::of($schemaPrice, self::$currency->value))],
            'min' => $schemaMin,
            'max' => $schemaMax,
            'required' => $required,
        ]));

        $this->product->schemas()->attach($schema->getKey());

        $this->product->load('schemas');

        $this->assertEquals(
            $minmax,
            $this->productService->getMinMaxPrices($this->product)
        );
    }

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public static function multiplyAnotherSchemaProvider(): array
    {
        $base = [
            $schemaBasePrice = 0, //schema hasn't price anymore
            $schemaMin = 2,
            $schemaMax = 5,
        ];

        return [
            'required' => array_merge($base, [
                true,
                [
                    PriceDto::fromMoney(Money::of(self::$price + $schemaBasePrice * $schemaMin, self::$currency->value)),
                    PriceDto::fromMoney(Money::of(self::$price + $schemaBasePrice * $schemaMax, self::$currency->value)),
                ],
            ]),
        ];
    }

    /**
     * @dataProvider multiplyAnotherSchemaProvider
     */
    public function testMinMaxPricesMultiplyAnotherSchemaOptional(
        float $schemaBasePrice,
        float $schemaMin,
        float $schemaMax,
        bool $required,
        array $minmax,
    ): void {
        /** @var Schema $baseSchema */
        $baseSchema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'Test',
            'prices' => [PriceDto::from(Money::of($schemaBasePrice, self::$currency->value))],
            'required' => true,
        ]));

        $this->product->schemas()->attach($baseSchema->getKey());

        /** @var Schema $multiplySchema */
        $multiplySchema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'Test2',
            'min' => $schemaMin,
            'max' => $schemaMax,
            'required' => $required,
        ]));

        $this->product->schemas()->attach($multiplySchema->getKey());

        $multiplySchema->usedSchemas()->attach($baseSchema);

        $this->product->load('schemas');

        $this->assertEquals(
            $minmax,
            $this->productService->getMinMaxPrices($this->product),
        );
    }

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function testMinMaxPricesMultipleSchemas(): void
    {
        $schema1Price = 0; //schema hasn't price anymore

        /** @var Schema $schema */
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'Test',
            'prices' => [PriceDto::from(Money::of($schema1Price, self::$currency->value))],
            'required' => true,
        ]));

        $this->product->schemas()->attach($schema);

        $schema2Price = 0; //schema hasn't price anymore
        /** @var Schema $schema2 */
        $schema2 = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'Test2',
            'prices' => [PriceDto::from(Money::of($schema2Price, self::$currency->value))],
            'required' => false,
        ]));

        $this->product->schemas()->attach($schema2);

        $schema3Price = 0; //schema hasn't price anymore
        /** @var Schema $schema3 */
        $schema3 = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'Test3',
            'prices' => [PriceDto::from(Money::of($schema3Price, self::$currency->value))],
            'required' => false,
        ]));

        $this->product->schemas()->attach($schema3);

        $this->product->load('schemas');

        $this->assertEquals(
            [
                PriceDto::fromMoney(Money::of(self::$price + $schema1Price, self::$currency->value)),
                PriceDto::fromMoney(Money::of(self::$price + $schema1Price + $schema2Price + $schema3Price, self::$currency->value)),
            ],
            $this->productService->getMinMaxPrices($this->product),
        );
    }
}
