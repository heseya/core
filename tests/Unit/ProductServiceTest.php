<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductServiceContract $productService;

    public function setUp(): void
    {
        parent::setUp();

        $this->productService = App::make(ProductServiceContract::class);
    }

    public function testMinMaxPricesNoSchemas(): void
    {
        $price = 10;
        $product = Product::factory()->create([
            'price' => $price,
        ]);

        $this->assertEquals(
            [$price, $price],
            $this->productService->getMinMaxPrices($product),
        );
    }

    public function schemaProvider(): array
    {
        $base = [
            $price = 10,
            $schemaPrice = 5,
        ];

        return [
            'optional schema' => array_merge(
                $base,
                [0, false, [$price, $price + $schemaPrice]]
            ),
            'boolean schema' => array_merge(
                $base,
                [2, true, [$price, $price + $schemaPrice]],
            ),
            'required schema' => array_merge(
                $base,
                [0, true, [$price + $schemaPrice, $price + $schemaPrice]],
            ),
        ];
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testMinMaxPricesSchema(
        float $price,
        float $schemaPrice,
        int $type,
        bool $required,
        array $minmax,
    ): void {
        /** @var Product $product */
        $product = Product::factory()->create([
            'price' => $price,
        ]);

        $product->schemas()->create([
            'name' => 'Test',
            'type' => $type,
            'price' => $schemaPrice,
            'required' => $required,
        ]);

        $this->assertEquals(
            $minmax,
            $this->productService->getMinMaxPrices($product),
        );
    }

    public function selectSchemaProvider(): array
    {
        $base = [
            $price = 10,
            $schemaPrice = 5,
            $optionPriceLowest = 5,
            $optionPriceMedium = 7,
            $optionPriceHighest = 10,
        ];

        return [
            'optional' => array_merge($base, [
                false,
                [
                    $price,
                    $price + $schemaPrice + $optionPriceHighest,
                ],
            ]),
            'required' => array_merge($base, [
                true,
                [
                    $price + $schemaPrice + $optionPriceLowest,
                    $price + $schemaPrice + $optionPriceHighest,
                ],
            ]),
        ];
    }

    /**
     * @dataProvider selectSchemaProvider
     */
    public function testMinMaxPricesSelectSchema(
        float $price,
        float $schemaPrice,
        float $optionPriceLowest,
        float $optionPriceMedium,
        float $optionPriceHighest,
        bool $required,
        array $minmax,
    ): void {
        /** @var Product $product */
        $product = Product::factory()->create([
            'price' => $price,
        ]);

        /** @var Schema $schema */
        $schema = $product->schemas()->create([
            'name' => 'Test',
            'type' => 4,
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

        $this->assertEquals(
            $minmax,
            $this->productService->getMinMaxPrices($product),
        );
    }

    public function multiplySchemaProvider(): array
    {
        $base = [
            $price = 10,
            $schemaPrice = 5,
            $schemaMin = 2,
            $schemaMax = 5,
        ];

        return [
            'optional' => array_merge($base, [
                false,
                [$price, $price + $schemaPrice * $schemaMax],
            ]),
            'required' => array_merge($base, [
                true,
                [
                    $price + $schemaPrice * $schemaMin,
                    $price + $schemaPrice * $schemaMax,
                ],
            ]),
        ];
    }

    /**
     * @dataProvider multiplySchemaProvider
     */
    public function testMinMaxPricesMultiplySchema(
        float $price,
        float $schemaPrice,
        float $schemaMin,
        float $schemaMax,
        bool $required,
        array $minmax,
    ): void {
        /** @var Product $product */
        $product = Product::factory()->create([
            'price' => $price,
        ]);

        $product->schemas()->create([
            'name' => 'Test',
            'type' => 6,
            'price' => $schemaPrice,
            'min' => $schemaMin,
            'max' => $schemaMax,
            'required' => $required,
        ]);

        $this->assertEquals(
            $minmax,
            $this->productService->getMinMaxPrices($product),
        );
    }

    public function multiplyAnotherSchemaProvider(): array
    {
        $base = [
            $price = 10,
            $schemaBasePrice = 5,
            $schemaMin = 2,
            $schemaMax = 5,
        ];

        return [
            'optional' => array_merge($base, [
                false,
                [
                    $price,
                    $price + $schemaBasePrice * $schemaMax,
                ],
            ]),
            'required' => array_merge($base, [
                true,
                [
                    $price + $schemaBasePrice * $schemaMin,
                    $price + $schemaBasePrice * $schemaMax,
                ],
            ]),
        ];
    }

    /**
     * @dataProvider multiplyAnotherSchemaProvider
     */
    public function testMinMaxPricesMultiplyAnotherSchemaOptional(
        float $price,
        float $schemaBasePrice,
        float $schemaMin,
        float $schemaMax,
        bool $required,
        array $minmax,
    ): void {
        /** @var Product $product */
        $product = Product::factory()->create([
            'price' => $price,
        ]);

        /** @var Schema $baseSchema */
        $baseSchema = $product->schemas()->create([
            'name' => 'Test',
            'type' => 0,
            'price' => $schemaBasePrice,
            'required' => true,
        ]);;

        /** @var Schema $multiplySchema */
        $multiplySchema = $product->schemas()->create([
            'name' => 'Test2',
            'type' => 7,
            'min' => $schemaMin,
            'max' => $schemaMax,
            'required' => $required,
        ]);

        $multiplySchema->usedSchemas()->attach($baseSchema);

        $this->assertEquals(
            $minmax,
            $this->productService->getMinMaxPrices($product),
        );
    }

    public function testMinMaxPricesMultipleSchemas(): void
    {
        $price = 10;
        /** @var Product $product */
        $product = Product::factory()->create([
            'price' => $price,
        ]);

        $schema1Price = 5;
        $product->schemas()->create([
            'name' => 'Test',
            'type' => 0,
            'price' => $schema1Price,
            'required' => true,
        ]);

        $schema2Price = 7;
        $product->schemas()->create([
            'name' => 'Test2',
            'type' => 1,
            'price' => $schema2Price,
            'required' => false,
        ]);

        $schema3Price = 10;
        $product->schemas()->create([
            'name' => 'Test3',
            'type' => 2,
            'price' => $schema3Price,
            'required' => false,
        ]);

        $this->assertEquals(
            [
                $price + $schema1Price,
                $price + $schema1Price + $schema2Price + $schema3Price,
            ],
            $this->productService->getMinMaxPrices($product),
        );
    }
}
