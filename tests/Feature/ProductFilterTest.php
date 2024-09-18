<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Product;
use App\Services\ProductService;
use Domain\Currency\Currency;
use Domain\ProductSchema\Services\SchemaCrudService;
use Domain\SalesChannel\Enums\SalesChannelActivityType;
use Domain\SalesChannel\Enums\SalesChannelStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class ProductFilterTest extends TestCase
{
    use RefreshDatabase;

    private SchemaCrudService $schemaCrudService;

    public function setUp(): void
    {
        parent::setUp();

        $this->schemaCrudService = App::make(SchemaCrudService::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testHasItems($user): void
    {
        $this->{$user}->givePermissionTo(['products.show', 'products.show_hidden']);

        $productWithoutItems = Product::factory()->create();
        $item = Item::factory()->create();

        /** @var Product $productWithItems */
        $productWithItems = Product::factory()->create();
        $productWithItems->items()->attach([$item->getKey() => ['required_quantity' => 1]]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['has_items' => true])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $productWithItems->getKey()])
            ->assertJsonMissing(['id' => $productWithoutItems->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testHasSchemas($user): void
    {
        $this->{$user}->givePermissionTo(['products.show', 'products.show_hidden']);

        $productWithoutSchemas = Product::factory()->create();

        /** @var Product $productWithSchemas */
        $productWithSchemas = Product::factory()->create();

        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'product_id' => $productWithSchemas->getKey(),
        ]));

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['has_schemas' => true])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $productWithSchemas->getKey()])
            ->assertJsonMissing(['id' => $productWithoutSchemas->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShippingDigital($user): void
    {
        $this->{$user}->givePermissionTo(['products.show', 'products.show_hidden']);

        $productPhysical = Product::factory()->create(['shipping_digital' => false]);
        $productDigital = Product::factory()->create(['shipping_digital' => true]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/products', ['shipping_digital' => true])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $productDigital->getKey()])
            ->assertJsonMissing(['id' => $productPhysical->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductMaxPrice(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show']);

        $saleChannel = SalesChannel::factory()->create([
            'activity' => SalesChannelActivityType::ACTIVE->value,
            'vat_rate' => '0.0',
            'status' => SalesChannelStatus::PUBLIC->value,
            'price_map_id' => Currency::DEFAULT->getDefaultPriceMapId(),
        ]);

        $product1 = $this->prepareProduct('10.00');
        $product2 = $this->prepareProduct('8.00');

        $this
            ->actingAs($this->{$user})
            ->json(
                'GET',
                '/products?price.currency=' . Currency::DEFAULT->value . '&price.max=10',
                headers: ['X-Sales-Channel' => $saleChannel->getKey()],
            )
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $product2->getKey(),
            ])
            ->assertJsonFragment([
                'net' => '8.00',
                'gross' => '8.00',
            ])
            ->assertJsonFragment([
                'id' => $product1->getKey(),
            ])
            ->assertJsonFragment([
                'net' => '10.00',
                'gross' => '10.00',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductMaxPriceWithVat(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show']);

        $saleChannel = SalesChannel::factory()->create([
            'activity' => SalesChannelActivityType::ACTIVE->value,
            'vat_rate' => '25.0',
            'status' => SalesChannelStatus::PUBLIC->value,
            'price_map_id' => Currency::DEFAULT->getDefaultPriceMapId(),
        ]);

        $product1 = $this->prepareProduct('11.00');
        $product2 = $this->prepareProduct('8.00');

        $this
            ->actingAs($this->{$user})
            ->json(
                'GET',
                '/products?price.currency=' . Currency::DEFAULT->value . '&price.max=10',
                headers: ['X-Sales-Channel' => $saleChannel->getKey()],
            )
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $product2->getKey(),
            ])
            ->assertJsonFragment([
                'net' => '8.00',
                'gross' => '10.00',
            ])
            ->assertJsonMissing([
                'id' => $product1->getKey(),
            ])
            ->assertJsonMissing([
                'net' => '11.00',
                'gross' => '13.75',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductMaxPriceWithVatHiddenChannel(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show']);

        // Default from migration with vat_rate 0
        $defaultChannel = SalesChannel::query()->where('default', '=', true)->first();

        /**
         * I'm not sure what result should this really return, how is user access to PRIVATE SalesChannel determined?
         */
        $saleChannel = SalesChannel::factory()->create([
            'activity' => SalesChannelActivityType::ACTIVE->value,
            'vat_rate' => '25.0',
            'status' => SalesChannelStatus::PRIVATE->value,
            'price_map_id' => Currency::DEFAULT->getDefaultPriceMapId(),
        ]);

        $product1 = $this->prepareProduct('10.00');
        $product2 = $this->prepareProduct('8.00');

        $this
            ->actingAs($this->{$user})
            ->json(
                'GET',
                '/products?price.currency=' . Currency::DEFAULT->value . '&price.max=10',
                headers: ['X-Sales-Channel' => $saleChannel->getKey()],
            )
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $product2->getKey(),
            ])
            ->assertJsonFragment([
                'net' => '8.00',
                'gross' => '8.00',
            ])
            ->assertJsonFragment([
                'id' => $product1->getKey(),
            ])
            ->assertJsonFragment([
                'net' => '10.00',
                'gross' => '10.00',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductMaxPriceWithVatRoundingCheck(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show']);

        $saleChannel = SalesChannel::factory()->create([
            'activity' => SalesChannelActivityType::ACTIVE->value,
            'vat_rate' => '12',
            'status' => SalesChannelStatus::PUBLIC->value,
            'price_map_id' => Currency::DEFAULT->getDefaultPriceMapId(),
        ]);

        $product1 = $this->prepareProduct('11.00');
        $product2 = $this->prepareProduct('8.00');
        $product3 = $this->prepareProduct('7.09');

        $this
            ->actingAs($this->{$user})
            ->json(
                'GET',
                '/products?price.currency=' . Currency::DEFAULT->value . '&price.max=10',
                headers: ['X-Sales-Channel' => $saleChannel->getKey()],
            )
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $product2->getKey(),
            ])
            ->assertJsonFragment([
                'net' => '8.00',
                'gross' => '8.96',
            ])
            ->assertJsonFragment([
                'id' => $product3->getKey(),
            ])
            ->assertJsonFragment([
                'net' => '7.09',
                'gross' => '7.94',
            ])
            ->assertJsonMissing([
                'id' => $product1->getKey(),
            ])
            ->assertJsonMissing([
                'net' => '11.00',
                'gross' => '12.32',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductMinPrice(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show']);

        $saleChannel = SalesChannel::factory()->create([
            'activity' => SalesChannelActivityType::ACTIVE->value,
            'vat_rate' => '0.0',
            'status' => SalesChannelStatus::PUBLIC->value,
            'price_map_id' => Currency::DEFAULT->getDefaultPriceMapId(),
        ]);

        $product1 = $this->prepareProduct('10.00');
        $product2 = $this->prepareProduct('8.00');

        $this
            ->actingAs($this->{$user})
            ->json(
                'GET',
                '/products?price.currency=' . Currency::DEFAULT->value . '&price.min=10',
                headers: ['X-Sales-Channel' => $saleChannel->getKey()],
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing([
                'id' => $product2->getKey(),
            ])
            ->assertJsonMissing([
                'net' => '8.00',
                'gross' => '8.00',
            ])
            ->assertJsonFragment([
                'id' => $product1->getKey(),
            ])
            ->assertJsonFragment([
                'net' => '10.00',
                'gross' => '10.00',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductMinPriceWithVat(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show']);

        $saleChannel = SalesChannel::factory()->create([
            'activity' => SalesChannelActivityType::ACTIVE->value,
            'vat_rate' => '25.0',
            'status' => SalesChannelStatus::PUBLIC->value,
            'price_map_id' => Currency::DEFAULT->getDefaultPriceMapId(),
        ]);

        $product1 = $this->prepareProduct('10.00');
        $product2 = $this->prepareProduct('8.00');

        $this
            ->actingAs($this->{$user})
            ->json(
                'GET',
                '/products?price.currency=' . Currency::DEFAULT->value . '&price.min=10',
                headers: ['X-Sales-Channel' => $saleChannel->getKey()],
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing([
                'id' => $product2->getKey(),
            ])
            ->assertJsonMissing([
                'net' => '8.00',
                'gross' => '10.00',
            ])
            ->assertJsonFragment([
                'id' => $product1->getKey(),
            ])
            ->assertJsonFragment([
                'net' => '10.00',
                'gross' => '12.50',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductMinPriceWithVatRoundingCheck(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show']);

        $saleChannel = SalesChannel::factory()->create([
            'activity' => SalesChannelActivityType::ACTIVE->value,
            'vat_rate' => '12',
            'status' => SalesChannelStatus::PUBLIC->value,
            'price_map_id' => Currency::DEFAULT->getDefaultPriceMapId(),
        ]);

        $product1 = $this->prepareProduct('10.00');
        $product2 = $this->prepareProduct('8.00');
        $product3 = $this->prepareProduct('7.09');

        $this
            ->actingAs($this->{$user})
            ->json(
                'GET',
                '/products?price.currency=' . Currency::DEFAULT->value . '&price.min=10',
                headers: ['X-Sales-Channel' => $saleChannel->getKey()],
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing([
                'id' => $product2->getKey(),
            ])
            ->assertJsonMissing([
                'net' => '8.00',
                'gross' => '8.96',
            ])
            ->assertJsonFragment([
                'id' => $product1->getKey(),
            ])
            ->assertJsonFragment([
                'net' => '10.00',
                'gross' => '11.20',
            ])
            ->assertJsonMissing([
                'id' => $product3->getKey(),
            ])
            ->assertJsonMissing([
                'net' => '7.09',
                'gross' => '7.94',
            ]);
    }

    private function prepareProduct(string $price): Product
    {
        $productPrices = array_map(fn(Currency $currency) => [
            'value' => $price,
            'currency' => $currency->value,
        ], Currency::cases());

        return app(ProductService::class)->create(
            FakeDto::productCreateDto([
                'shipping_digital' => false,
                'public' => true,
                'prices_base' => $productPrices,
            ])
        );
    }
}
