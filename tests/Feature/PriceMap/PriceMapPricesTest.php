<?php

namespace Tests\Feature\Organizations;

use App\Models\Option;
use App\Models\Product;
use Database\Seeders\PriceMapSeeder;
use Domain\Currency\Currency;
use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapProductPrice;
use Domain\PriceMap\PriceMapService;
use Domain\ProductSchema\Models\Schema;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

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

    public function setUp(): void
    {
        parent::setUp();

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

        App::make(PriceMapSeeder::class)->run();
        $this->priceMap1 = PriceMap::find(Currency::DEFAULT->getDefaultPriceMapId());

        $this->priceMap2 = PriceMap::factory()->create([
            'currency' => Currency::DEFAULT->value,
        ]);

        $priceMapService = App::make(PriceMapService::class);
        $priceMapService->createPricesForAllMissingProductsAndSchemas($this->priceMap1);
        $priceMapService->createPricesForAllMissingProductsAndSchemas($this->priceMap2);

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
            // TODO uncomment after prices fix
//            ->assertJsonStructure([
//                'data' => [
//                    'products',
//                    'schema_options',
//                ],
//                'meta',
//            ])
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
}
