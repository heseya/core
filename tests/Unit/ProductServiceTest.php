<?php

namespace Tests\Unit;

use App\Enums\SchemaType;
use App\Models\Product;
use App\Models\Schema;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\ProductSchema\Services\SchemaCrudService;
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
            $schemaPrice = 5,
        ];

        return [
            'optional schema' => array_merge(
                $base,
                [
                    SchemaType::SELECT,
                    false,
                    PriceDto::fromMoney(Money::of(self::$price, self::$currency->value)),
                    PriceDto::fromMoney(Money::of(self::$price + $schemaPrice, self::$currency->value)),
                ],
            ),
            'required schema' => array_merge(
                $base,
                [
                    SchemaType::SELECT,
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
        SchemaType $type,
        bool $required,
        PriceDto $min,
        PriceDto $max,
    ): void {

        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'Test',
            'required' => $required,
            'product_id' => $this->product->getKey(),
        ]));
        $option = $schema->options()->create([
            'name' => 'Default option',
            'prices' => [['value' => $schemaPrice, 'currency' => self::$currency->value]],
        ]);

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
            $schemaPrice = 5,
            $optionPriceLowest = 5,
            $optionPriceMedium = 7,
            $optionPriceHighest = 10,
        ];

        return [
            'optional' => array_merge($base, [
                false,
                [
                    PriceDto::fromMoney(Money::of(self::$price, self::$currency->value)),
                    PriceDto::fromMoney(Money::of(self::$price + $schemaPrice + $optionPriceHighest, self::$currency->value)),
                ],
            ]),
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
        $options = [
            [
                'name' => 'opt1',
                'prices' => [PriceDto::from(Money::of($schemaPrice + $optionPriceLowest, self::$currency->value))],
            ],
            [
                'name' => 'opt2',
                'prices' => [PriceDto::from(Money::of($schemaPrice + $optionPriceMedium, self::$currency->value))],
            ],
            [
                'name' => 'opt3',
                'prices' => [PriceDto::from(Money::of($schemaPrice + $optionPriceHighest, self::$currency->value))],
            ]
        ];

        if (!$required) {
            $options[] = [
                'name' => 'default',
                'prices' => [PriceDto::from(Money::of(0, self::$currency->value))]
            ];
        }

        /** @var Schema $schema */
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'Test',
            'type' => SchemaType::SELECT,
            'required' => $required,
            'options' => $options,
            'product_id' => $this->product->getKey(),
        ]));

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
                    PriceDto::fromMoney(Money::of(self::$price, self::$currency->value)),
                    PriceDto::fromMoney(Money::of(self::$price + $schemaPrice * $schemaMax, self::$currency->value)),
                ],
            ]),
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
                    PriceDto::fromMoney(Money::of(self::$price, self::$currency->value)),
                    PriceDto::fromMoney(Money::of(self::$price + $schemaBasePrice * $schemaMax, self::$currency->value)),
                ],
            ]),
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
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function testMinMaxPricesMultipleSchemas(): void
    {
        $schema1Price = 5;

        /** @var Schema $schema */
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'Test',
            'required' => true,
            'product_id' => $this->product->getKey(),
        ]));
        $option = $schema->options()->create([
            'name' => 'Default option',
            'prices' => [['value' => $schema1Price, 'currency' => self::$currency->value]],
        ]);

        $schema2Price = 7;
        /** @var Schema $schema2 */
        $schema2 = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'Test2',
            'required' => false,
            'product_id' => $this->product->getKey(),
        ]));
        $option2 = $schema2->options()->create([
            'name' => 'Default option',
            'prices' => [['value' => $schema2Price, 'currency' => self::$currency->value]],
        ]);

        $schema3Price = 10;
        /** @var Schema $schema3 */
        $schema3 = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'Test3',
            'required' => false,
            'product_id' => $this->product->getKey(),
        ]));
        $option3 = $schema3->options()->create([
            'name' => 'Default option',
            'prices' => [['value' => $schema3Price, 'currency' => self::$currency->value]],
        ]);

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
