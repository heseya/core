<?php

namespace Tests\Feature\PriceMap;

use App\Enums\DiscountTargetType;
use App\Enums\ShippingType;
use App\Models\Address;
use App\Models\Discount;
use App\Models\Option;
use App\Models\PriceRange;
use App\Models\Product;
use App\Services\ProductService;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\PaymentMethods\Enums\PaymentMethodType;
use Domain\PaymentMethods\Models\PaymentMethod;
use Domain\Price\Dtos\PriceDto;
use Domain\PriceMap\PriceMap;
use Domain\ProductSchema\Models\Schema;
use Domain\ProductSchema\Services\SchemaCrudService;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelRepository;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class NetAndGrossTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private Currency $currency;
    private PriceMap $priceMap;
    private SalesChannel $salesChannel;

    private Product $product;
    private Schema $schema;
    private Option $option;

    private ShippingMethod $shippingMethod;
    private string $email;
    private Address $address;
    private PaymentMethod $paymentMethod;

    private ProductService $productService;
    private SchemaCrudService $schemaCrudService;
    private SalesChannelRepository $salesChannelRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->productService = App::make(ProductService::class);
        $this->schemaCrudService = App::make(SchemaCrudService::class);
        $this->salesChannelRepository = App::make(SalesChannelRepository::class);

        $this->currency = Currency::DEFAULT;

        $this->priceMap = PriceMap::query()->findOrFail($this->currency->getDefaultPriceMapId());

        $this->salesChannel = $this->salesChannelRepository->getDefault();
        $this->salesChannel->price_map_id = $this->priceMap->getKey();
        $this->salesChannel->vat_rate = 10;
        $this->salesChannel->save();

        $this->email = $this->faker->freeEmail;

        $this->shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::ADDRESS,
        ]);

        $range = PriceRange::query()->create([
            'start' => Money::zero($this->currency->value),
            'value' => Money::of(10, $this->currency->value),
        ]);
        $this->shippingMethod->priceRanges()->saveMany([$range]);

        $this->product = $this->productService->create(
            FakeDto::productCreateDto([
                'public' => true,
                'prices_base' => [PriceDto::from(Money::of(100.0, $this->currency->value))],
            ])
        );

        $this->schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'hidden' => false,
                'required' => false,
                'options' => [
                    [
                        'name' => 'Default',
                        'prices' => [PriceDto::from(Money::of(0, $this->currency->value))],
                    ],
                    [
                        'name' => 'Priced',
                        'prices' => [PriceDto::from(Money::of(100, $this->currency->value))],
                    ],
                ],
                'product_id' => $this->product->getKey(),
            ])
        );

        $this->option = $this->schema->options->where('name', 'Priced')->first();

        $this->address = Address::factory()->make();

        $this->paymentMethod = PaymentMethod::factory()->create([
            'type' => PaymentMethodType::PREPAID,
        ]);
    }

    public function testCartForNetPriceMap(): void
    {
        $this->user->givePermissionTo('cart.verify');

        $this->priceMap->is_net = true;
        $this->priceMap->save();

        $discount1 = Discount::factory()->create([
            'description' => 'Test',
            'code' => null,
            'percentage' => '10',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);
        $discount1->products()->attach($this->product->getKey());

        $discount2 = Discount::factory()->create([
            'description' => 'Test',
            'code' => null,
            'percentage' => '10',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ]);
        $discount2->products()->attach($this->product->getKey());

        $this->productService->updateMinPrices($this->product, collect([$this->salesChannel]));

        $response = $this->actingAs($this->user)->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $this->schema->getKey() => $this->option->getKey(),
                    ],
                ],
            ],
        ]);

        $response
            ->assertValid()
            ->assertOk();

        $response->assertJsonFragment([
            'cart_total_initial' => [
                'net' => '200.00',
                'gross' => '220.00',
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'cart_total' => [
                'net' => '162.00',
                'gross' => '178.20',
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'shipping_price_initial' => [
                'net' => '9.09',
                'gross' => '10.00',
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'shipping_price' => [
                'net' => '9.09',
                'gross' => '10.00',
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'summary' => [
                'net' => '171.09',
                'gross' => '188.20', // 178.20 + 10 or alternatively 230 - 22 - 19.8, where 22 is 10% discount of product price, and 19.8 is 10% discount of remaining order value
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
        ]);
    }

    public function testCartForGrossPriceMap(): void
    {
        $this->user->givePermissionTo('cart.verify');

        $this->priceMap->is_net = false;
        $this->priceMap->save();

        $discount1 = Discount::factory()->create([
            'description' => 'Test',
            'code' => null,
            'percentage' => '10',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);
        $discount1->products()->attach($this->product->getKey());

        $discount2 = Discount::factory()->create([
            'description' => 'Test',
            'code' => null,
            'percentage' => '10',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ]);
        $discount2->products()->attach($this->product->getKey());

        $this->productService->updateMinPrices($this->product, collect([$this->salesChannel]));

        $response = $this->actingAs($this->user)->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $this->schema->getKey() => $this->option->getKey(),
                    ],
                ],
            ],
        ]);

        $response
            ->assertValid()
            ->assertOk();

        $response->assertJsonFragment([
            'cart_total_initial' => [
                'net' => '181.82',
                'gross' => '200.00',
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'cart_total' => [
                'net' => '147.27',
                'gross' => '162.00', // 200 - 20 - 18 
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'shipping_price_initial' => [
                'net' => '9.09',
                'gross' => '10.00',
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'shipping_price' => [
                'net' => '9.09',
                'gross' => '10.00',
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'summary' => [
                'net' => '156.36',
                'gross' => '172.00', // 162 + 10 or alternatively 200 - 20 - 18, where 20 is 10% discount of product price, and 18 is 10% discount of remaining order value
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
        ]);
    }

    public function testOrderForNetPriceMap(): void
    {
        $this->user->givePermissionTo('cart.verify');
        $this->user->givePermissionTo('orders.add');

        $this->priceMap->is_net = true;
        $this->priceMap->save();

        $discount1 = Discount::factory()->create([
            'description' => 'Test',
            'code' => null,
            'percentage' => '10',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);
        $discount1->products()->attach($this->product->getKey());

        $discount2 = Discount::factory()->create([
            'description' => 'Test',
            'code' => null,
            'percentage' => '10',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ]);
        $discount2->products()->attach($this->product->getKey());

        $this->productService->updateMinPrices($this->product, collect([$this->salesChannel]));

        $response = $this->actingAs($this->user)->json('POST', '/orders', [
            'sales_channel_id' => $this->salesChannel->getKey(),
            'currency' => $this->currency,
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $this->schema->getKey() => $this->option->getKey(),
                    ]
                ],
            ],
            'payment_method_id' => $this->paymentMethod->getKey(),
        ]);

        $response
            ->assertValid()
            ->assertCreated();

        $response->assertJsonFragment([
            'cart_total_initial' => [
                'net' => '200.00',
                'gross' => '220.00',
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'cart_total' => [
                'net' => '162.00',
                'gross' => '178.20',
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'shipping_price_initial' => [
                'net' => '9.09',
                'gross' => '10.00',
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'shipping_price' => [
                'net' => '9.09',
                'gross' => '10.00',
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'summary' => [
                'net' => '171.09',
                'gross' => '188.20', // 178.20 + 10 or alternatively 230 - 22 - 19.8, where 22 is 10% discount of product price, and 19.8 is 10% discount of remaining order value
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
        ]);
    }

    public function testOrderForGrossPriceMap(): void
    {
        $this->user->givePermissionTo('cart.verify');
        $this->user->givePermissionTo('orders.add');

        $this->priceMap->is_net = false;
        $this->priceMap->save();

        $discount1 = Discount::factory()->create([
            'description' => 'Test',
            'code' => null,
            'percentage' => '10',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);
        $discount1->products()->attach($this->product->getKey());

        $discount2 = Discount::factory()->create([
            'description' => 'Test',
            'code' => null,
            'percentage' => '10',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ]);
        $discount2->products()->attach($this->product->getKey());

        $this->productService->updateMinPrices($this->product, collect([$this->salesChannel]));

        $response = $this->actingAs($this->user)->json('POST', '/orders', [
            'sales_channel_id' => $this->salesChannel->getKey(),
            'currency' => $this->currency,
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $this->schema->getKey() => $this->option->getKey(),
                    ]
                ],
            ],
            'payment_method_id' => $this->paymentMethod->getKey(),
        ]);

        $response
            ->assertValid()
            ->assertCreated();

        $response->assertJsonFragment([
            'cart_total_initial' => [
                'net' => '181.82',
                'gross' => '200.00',
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'cart_total' => [
                'net' => '147.27',
                'gross' => '162.00', // 200 - 20 - 18 
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'shipping_price_initial' => [
                'net' => '9.09',
                'gross' => '10.00',
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'shipping_price' => [
                'net' => '9.09',
                'gross' => '10.00',
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
            'summary' => [
                'net' => '156.36',
                'gross' => '172.00', // 162 + 10 or alternatively 200 - 20 - 18, where 20 is 10% discount of product price, and 18 is 10% discount of remaining order value
                'vat_rate' => '0.10',
                'currency' => 'PLN',
            ],
        ]);
    }
}
