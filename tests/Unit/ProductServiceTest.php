<?php

namespace Tests\Unit;

use App\Dtos\PriceDto;
use App\Enums\Currency;
use App\Enums\SchemaType;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\ProductServiceContract;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class ProductServiceTest extends TestCase
{
    private ProductServiceContract $productService;

    private static Currency $currency = Currency::DEFAULT;
    private static int $price = 10;
    private Product $product;

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->productService = App::make(ProductServiceContract::class);

        // @var Product $product
        $this->product = $this->productService->create(FakeDto::productCreateDto([
            'prices_base' => [new PriceDto(Money::of(self::$price, Currency::DEFAULT->value))],
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
                [new PriceDto(Money::of(self::$price, self::$currency->value))],
                [new PriceDto(Money::of(self::$price, self::$currency->value))],
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
            $schemaPrice = 5,
        ];

        return [
            'optional schema' => array_merge(
                $base,
                [SchemaType::STRING, false, [
                    [new PriceDto(Money::of(self::$price, self::$currency->value))],
                    [new PriceDto(Money::of(self::$price + $schemaPrice, self::$currency->value))],
                ]]
            ),
            'boolean schema' => array_merge(
                $base,
                [SchemaType::BOOLEAN, true, [
                    [new PriceDto(Money::of(self::$price, self::$currency->value))],
                    [new PriceDto(Money::of(self::$price + $schemaPrice, self::$currency->value))],
                ]],
            ),
            'required schema' => array_merge(
                $base,
                [SchemaType::STRING, true, [
                    [new PriceDto(Money::of(self::$price + $schemaPrice, self::$currency->value))],
                    [new PriceDto(Money::of(self::$price + $schemaPrice, self::$currency->value))],
                ]],
            ),
        ];
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testMinMaxPricesSchema(
        float $schemaPrice,
        int $type,
        bool $required,
        array $minmax,
    ): void {
        $this->product->schemas()->create([
            'name' => 'Test',
            'type' => $type,
            'price' => $schemaPrice,
            'required' => $required,
        ]);

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
    public static function selectSchemaProvider(): array
    {
        $base = [
            $schemaPrice = 5,
            $optionPriceLowest = 5,
            $optionPriceMedium = 7,
            $optionPriceHighest = 10,
        ];

        return [
            'optional' => array_merge($base, [
                false,
                [
                    [new PriceDto(Money::of(self::$price, self::$currency->value))],
                    [new PriceDto(Money::of(self::$price + $schemaPrice + $optionPriceHighest, self::$currency->value))],
                ],
            ]),
            'required' => array_merge($base, [
                true,
                [
                    [new PriceDto(Money::of(self::$price + $schemaPrice + $optionPriceLowest, self::$currency->value))],
                    [new PriceDto(Money::of(self::$price + $schemaPrice + $optionPriceHighest, self::$currency->value))],
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
        $schema = $this->product->schemas()->create([
            'name' => 'Test',
            'type' => SchemaType::SELECT,
            'price' => $schemaPrice,
            'required' => $required,
        ]);

        $schema->options()->create([
            'name' => 'opt1',
            'price' => $optionPriceLowest,
        ]);
        $schema->options()->create([
            'name' => 'opt2',
            'price' => $optionPriceMedium,
        ]);
        $schema->options()->create([
            'name' => 'opt3',
            'price' => $optionPriceHighest,
        ]);

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
            $schemaPrice = 5,
            $schemaMin = 2,
            $schemaMax = 5,
        ];

        return [
            'optional' => array_merge($base, [
                false,
                [
                    [new PriceDto(Money::of(self::$price, self::$currency->value))],
                    [new PriceDto(Money::of(self::$price + $schemaPrice * $schemaMax, self::$currency->value))],
                ],
            ]),
            'required' => array_merge($base, [
                true,
                [
                    [new PriceDto(Money::of(self::$price + $schemaPrice * $schemaMin, self::$currency->value))],
                    [new PriceDto(Money::of(self::$price + $schemaPrice * $schemaMax, self::$currency->value))],
                ],
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
        $this->product->schemas()->create([
            'name' => 'Test',
            'type' => SchemaType::MULTIPLY,
            'price' => $schemaPrice,
            'min' => $schemaMin,
            'max' => $schemaMax,
            'required' => $required,
        ]);

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
    public static function multiplyAnotherSchemaProvider(): array
    {
        $base = [
            $schemaBasePrice = 5,
            $schemaMin = 2,
            $schemaMax = 5,
        ];

        return [
            'optional' => array_merge($base, [
                false,
                [
                    [new PriceDto(Money::of(self::$price, self::$currency->value))],
                    [new PriceDto(Money::of(self::$price + $schemaBasePrice * $schemaMax, self::$currency->value))],
                ],
            ]),
            'required' => array_merge($base, [
                true,
                [
                    [new PriceDto(Money::of(self::$price + $schemaBasePrice * $schemaMin, self::$currency->value))],
                    [new PriceDto(Money::of(self::$price + $schemaBasePrice * $schemaMax, self::$currency->value))],
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
        $baseSchema = $this->product->schemas()->create([
            'name' => 'Test',
            'type' => SchemaType::STRING,
            'price' => $schemaBasePrice,
            'required' => true,
        ]);

        /** @var Schema $multiplySchema */
        $multiplySchema = $this->product->schemas()->create([
            'name' => 'Test2',
            'type' => SchemaType::MULTIPLY_SCHEMA,
            'min' => $schemaMin,
            'max' => $schemaMax,
            'required' => $required,
        ]);

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
        $schema1Price = 5;
        $this->product->schemas()->create([
            'name' => 'Test',
            'type' => SchemaType::STRING,
            'price' => $schema1Price,
            'required' => true,
        ]);

        $schema2Price = 7;
        $this->product->schemas()->create([
            'name' => 'Test2',
            'type' => SchemaType::NUMERIC,
            'price' => $schema2Price,
            'required' => false,
        ]);

        $schema3Price = 10;
        $this->product->schemas()->create([
            'name' => 'Test3',
            'type' => SchemaType::BOOLEAN,
            'price' => $schema3Price,
            'required' => false,
        ]);

        $this->product->load('schemas');

        $this->assertEquals(
            [
                [new PriceDto(Money::of(self::$price + $schema1Price, self::$currency->value))],
                [new PriceDto(Money::of(self::$price + $schema1Price + $schema2Price + $schema3Price, self::$currency->value))],
            ],
            $this->productService->getMinMaxPrices($this->product),
        );
    }
}
