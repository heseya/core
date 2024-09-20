<?php

namespace Tests\Feature\PriceMap;

use App\Models\Option;
use App\Models\Product;
use App\Services\ProductService;
use Domain\Currency\Currency;
use Domain\PriceMap\Jobs\RefreshCachedPricesForSalesChannel;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapProductPrice;
use Domain\PriceMap\PriceMapService;
use Domain\ProductSchema\Models\Schema;
use Domain\SalesChannel\Dtos\SalesChannelUpdateDto;
use Domain\SalesChannel\SalesChannelRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class PriceMapPricesTest extends TestCase
{
    private Product $product1;
    private Product $product2;
    private Schema $schema1;
    private Schema $schema2;
    private Option $option1a;
    private Option $option1b;
    private Option $option2a;
    private Option $option2b;
    private PriceMap $priceMap1;
    private PriceMap $priceMap2;

    private PriceMapService $priceMapService;

    public function setUp(): void
    {
        parent::setUp();

        $this->priceMapService = App::make(PriceMapService::class);

        $this->product1 = Product::factory()->create([
            'public' => true,
            'name' => 'foofoo'
        ]);
        $this->schema1 = Schema::factory()->create(['product_id' => $this->product1->id]);
        $this->option1a = Option::factory()->create(['schema_id' => $this->schema1->id]);
        $this->option1b = Option::factory()->create(['schema_id' => $this->schema1->id]);

        $this->product2 = Product::factory()->create([
            'public' => true,
            'name' => 'barbar',
        ]);
        $this->schema2 = Schema::factory()->create(['product_id' => $this->product2->id]);
        $this->option2a = Option::factory()->create(['schema_id' => $this->schema2->id]);
        $this->option2b = Option::factory()->create(['schema_id' => $this->schema2->id]);

        $this->priceMap1 = PriceMap::find(Currency::DEFAULT->getDefaultPriceMapId());

        $this->priceMap2 = PriceMap::factory()->create([
            'currency' => Currency::DEFAULT->value,
        ]);

        $this->priceMapService->createPricesForAllMissingProductsAndSchemas($this->priceMap1);
        $this->priceMapService->createPricesForAllMissingProductsAndSchemas($this->priceMap2);

        PriceMapProductPrice::where(['price_map_id' => $this->priceMap1->getKey(), 'product_id' => $this->product1->getKey()])->update(['value' => 10100]);
        PriceMapProductPrice::where(['price_map_id' => $this->priceMap1->getKey(), 'product_id' => $this->product2->getKey()])->update(['value' => 10200]);
        PriceMapProductPrice::where(['price_map_id' => $this->priceMap2->getKey(), 'product_id' => $this->product1->getKey()])->update(['value' => 20100]);
        PriceMapProductPrice::where(['price_map_id' => $this->priceMap2->getKey(), 'product_id' => $this->product2->getKey()])->update(['value' => 20200]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchPrices(string $user): void
    {
        Config::set('search.use_scout', false);
        Config::set('search.use_full_text_query', false);
        Config::set('search.use_full_text_relevancy', true);

        $this->{$user}->givePermissionTo('price-maps.show_details');

        /** @var TestResponse $response */
        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/price-maps/id:' . $this->priceMap1->getKey() . '/prices');

        $response->assertOk()
            ->assertJsonFragment(['product_name' => 'foofoo'])
            ->assertJsonFragment(['product_name' => 'barbar'])
            ->assertJsonFragment(['product_price' => '101.00'])
            ->assertJsonFragment(['product_price' => '102.00']);

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/price-maps/id:' . $this->priceMap1->getKey() . '/prices', [
                'search' => 'barbar'
            ]);

        $response->assertOk()
            ->assertJsonMissing(['product_name' => 'foofoo'])
            ->assertJsonFragment(['product_name' => 'barbar'])
            ->assertJsonMissing(['product_price' => '101.00'])
            ->assertJsonFragment(['product_price' => '102.00']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdatePrices(string $user)
    {
        $this->{$user}->givePermissionTo('price-maps.edit');

        $response = $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/price-maps/id:' . $this->priceMap1->getKey() . '/prices', [
                'products' => [
                    [
                        'id' => $this->product1->getKey(),
                        'value' => '1337',
                    ]
                ],
                'schema_options' => [
                    [
                        'id' => $this->option1a->getKey(),
                        'value' => '2137'
                    ]
                ]
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    [
                        'product_id',
                        'product_price',
                        'product_name',
                        'schema_options',
                    ],
                ],
                'meta',
            ])
            ->assertJsonFragment(['product_price' => '1337.00'])
            ->assertJsonFragment(['schema_option_price' => '2137.00']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testListProductPrices(string $user)
    {

        $this->{$user}->givePermissionTo('price-maps.show_details');
        $this->{$user}->givePermissionTo('products.show');

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products/id:' . $this->product1->getKey() . '/prices');

        $response->assertOk()
            ->assertJsonFragment(['price' => '101.00'])
            ->assertJsonFragment(['price' => '201.00'])
            ->assertJsonMissing(['price' => '102.00'])
            ->assertJsonMissing(['price' => '202.00']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductPrices(string $user)
    {
        $this->{$user}->givePermissionTo('price-maps.show_details');
        $this->{$user}->givePermissionTo('price-maps.edit');
        $this->{$user}->givePermissionTo('products.show');
        $this->{$user}->givePermissionTo('products.edit');

        $response = $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/products/id:' . $this->product1->getKey()  . '/prices', [
                'prices' => [
                    [
                        'price_map_id' => $this->priceMap1->getKey(),
                        'price' => '103'
                    ],
                    [
                        'price_map_id' => $this->priceMap2->getKey(),
                        'price' => '203'
                    ]
                ]
            ]);

        $response->assertOk()
            ->assertJsonFragment(['price' => '103.00'])
            ->assertJsonFragment(['price' => '203.00'])
            ->assertJsonMissing(['price' => '102.00'])
            ->assertJsonMissing(['price' => '202.00']);

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/products/id:' . $this->product1->getKey() . '/prices');

        $response->assertOk()
            ->assertJsonFragment(['price' => '103.00'])
            ->assertJsonFragment(['price' => '203.00'])
            ->assertJsonMissing(['price' => '102.00'])
            ->assertJsonMissing(['price' => '202.00']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testListSchemaPrices(string $user)
    {
        $this->{$user}->givePermissionTo('price-maps.show_details');
        $this->{$user}->givePermissionTo('products.show');

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/schemas/id:' . $this->schema1->getKey() . '/prices');

        $response->assertOk()
            ->assertJsonFragment(['id' => $this->option1a->getKey(), 'price' => '0.00'])
            ->assertJsonFragment(['id' => $this->option1b->getKey(), 'price' => '0.00'])
            ->assertJsonMissingExact(['id' => $this->option2a->getKey(), 'price' => '0.00'])
            ->assertJsonMissingExact(['id' => $this->option2b->getKey(), 'price' => '0.00']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSchemaPrices(string $user)
    {
        $this->{$user}->givePermissionTo('price-maps.show_details');
        $this->{$user}->givePermissionTo('price-maps.edit');
        $this->{$user}->givePermissionTo('products.show');
        $this->{$user}->givePermissionTo('products.edit');

        $response = $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/schemas/id:' . $this->schema1->getKey()  . '/prices', [
                'prices' => [
                    [
                        'price_map_id' => $this->priceMap1->getKey(),
                        'options' => [
                            [
                                'id' => $this->option1a->getKey(),
                                'price' => '105',
                            ],
                            [
                                'id' => $this->option1b->getKey(),
                                'price' => '106',
                            ]
                        ]
                    ]
                ]
            ]);

        $response->assertOk()
            ->assertJsonFragment(['id' => $this->option1a->getKey(), 'price' => '105.00'])
            ->assertJsonFragment(['id' => $this->option1b->getKey(), 'price' => '106.00'])
            ->assertJsonMissingExact(['id' => $this->option2a->getKey(), 'price' => '0.00'])
            ->assertJsonMissingExact(['id' => $this->option2b->getKey(), 'price' => '0.00']);

        $response = $this
            ->actingAs($this->{$user})
            ->json('GET', '/schemas/id:' . $this->schema1->getKey() . '/prices');

        $response->assertOk()
            ->assertJsonFragment(['id' => $this->option1a->getKey(), 'price' => '105.00'])
            ->assertJsonFragment(['id' => $this->option1b->getKey(), 'price' => '106.00']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProcessSingle(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');
        $this->{$user}->givePermissionTo('cart.verify');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $this->priceMapService->updateOptionPricesForDefaultMaps($this->option1a, FakeDto::generatePricesInAllCurrencies([], 123));

        $response = $this
            ->actingAs($this->{$user})
            ->json('POST', '/products/id:' . $this->product1->getKey() . '/process', [
                'schemas' => [
                    $this->schema1->getKey() => $this->option1a->getKey(),
                ]
            ]);

        $response->assertOk()
            ->assertJsonFragment([
                'price_initial' => [
                    'net' => '101.00',
                    'gross' => '101.00',
                    'currency' => 'PLN',
                    'sales_channel_id' => $salesChannel->getKey(),
                ]
            ])
            ->assertJsonFragment([
                'price' => [
                    'net' => '224.00', // 101 + 123
                    'gross' => '224.00',
                    'currency' => 'PLN',
                    'sales_channel_id' => $salesChannel->getKey(),
                ]
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProcess(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');
        $this->{$user}->givePermissionTo('cart.verify');

        $salesChannel = app(SalesChannelRepository::class)->getDefault();

        $this->priceMapService->updateOptionPricesForDefaultMaps($this->option1a, FakeDto::generatePricesInAllCurrencies([], 123));
        $this->priceMapService->updateOptionPricesForDefaultMaps($this->option2a, FakeDto::generatePricesInAllCurrencies([], 123));

        $response = $this
            ->actingAs($this->{$user})
            ->json('POST', '/products/process', [
                'products' => [
                    [
                        'product_id' => $this->product1->getKey(),
                        'schemas' => [
                            $this->schema1->getKey() => $this->option1a->getKey(),
                        ],
                    ],
                    [
                        'product_id' => $this->product2->getKey(),
                        'schemas' => [
                            $this->schema2->getKey() => $this->option2a->getKey(),
                        ],
                    ]
                ],
            ]);

        $response->assertOk()
            ->assertJsonFragment([
                'product_id' => $this->product1->getKey(),
                'price_initial' => [
                    'net' => '101.00',
                    'gross' => '101.00',
                    'currency' => 'PLN',
                    'sales_channel_id' => $salesChannel->getKey(),
                ],
                'price' => [
                    'net' => '224.00', // 101 + 123
                    'gross' => '224.00',
                    'currency' => 'PLN',
                    'sales_channel_id' => $salesChannel->getKey(),
                ]
            ])
            ->assertJsonFragment([
                'product_id' => $this->product2->getKey(),
                'price_initial' => [
                    'net' => '102.00',
                    'gross' => '102.00',
                    'currency' => 'PLN',
                    'sales_channel_id' => $salesChannel->getKey(),
                ],
                'price' => [
                    'net' => '225.00', // 102 + 123
                    'gross' => '225.00',
                    'currency' => 'PLN',
                    'sales_channel_id' => $salesChannel->getKey(),
                ]
            ]);
    }


    /**
     * @dataProvider authProvider
     */
    public function testUpdateVatRate(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show');
        $this->{$user}->givePermissionTo('products.show_details');

        $queue = Queue::fake(RefreshCachedPricesForSalesChannel::class);

        $salesChannelRepository = app(SalesChannelRepository::class);
        $salesChannel = $salesChannelRepository->getDefault();

        $productService = app(ProductService::class);

        $salesChannelRepository->update($salesChannel, SalesChannelUpdateDto::from([
            'vat_rate' => 10,
        ]));

        $queue->assertPushed(RefreshCachedPricesForSalesChannel::class);
        $manualJob = new RefreshCachedPricesForSalesChannel($salesChannel);
        $manualJob->handle($productService);

        $response = $this
            ->actingAs($this->{$user})
            ->withHeader('X-Sales-Channel', $salesChannel->getKey())
            ->json('GET', '/products/id:' . $this->product1->getKey());

        $this->assertEquals('111.10', $response->json('data.price.gross'));

        $salesChannelRepository->update($salesChannel, SalesChannelUpdateDto::from([
            'vat_rate' => 20,
        ]));

        $queue->assertPushed(RefreshCachedPricesForSalesChannel::class);
        $manualJob = new RefreshCachedPricesForSalesChannel($salesChannel);
        $manualJob->handle($productService);

        $response2 = $this
            ->actingAs($this->{$user})
            ->withHeader('X-Sales-Channel', $salesChannel->getKey())
            ->json('GET', '/products/id:' . $this->product1->getKey());

        $this->assertEquals('121.20', $response2->json('data.price.gross'));
    }
}
