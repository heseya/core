<?php

namespace Tests\Feature;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\RoleType;
use App\Enums\ShippingType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Option;
use App\Models\Order;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\Role;
use App\Models\Schema;
use App\Models\Status;
use App\Repositories\DiscountRepository;
use App\Services\ProductService;
use App\Services\SchemaCrudService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Dto\DtoException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class CartPurchaseLimitTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private ShippingMethod $shippingMethod;
    private Product $product;
    private Product $productWithSchema;
    private Schema $schema;
    private Option $option;
    private Option $option2;
    private ProductService $productService;
    private Currency $currency;
    private DiscountRepository $discountRepository;

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     * @throws DtoException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::ADDRESS,
        ]);

        $this->productService = App::make(ProductService::class);
        $this->currency = Currency::DEFAULT;

        $schemaCrudService = App::make(SchemaCrudService::class);

        /** @var PriceRange $lowRange */
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero($this->currency->value),
            'value' => Money::of(8.11, $this->currency->value),
        ]);

        /** @var PriceRange $highRange */
        $highRange = PriceRange::query()->create([
            'start' => Money::of(210, $this->currency->value),
            'value' => Money::of(0.0, $this->currency->value),
        ]);

        $this->shippingMethod->priceRanges()->saveMany([$lowRange, $highRange]);

        $this->product = $this->productService->create(
            FakeDto::productCreateDto([
                'public' => true,
                'prices_base' => [PriceDto::from(Money::of(4600.0, $this->currency->value))],
            ])
        );

        $this->productWithSchema = $this->productService->create(
            FakeDto::productCreateDto([
                'public' => true,
                'prices_base' => [PriceDto::from(Money::of(100.0, $this->currency->value))],
            ])
        );

        $this->schema = $schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 'select',
                'prices' => [PriceDto::from(Money::of(0, $this->currency->value))],
                'hidden' => false,
                'required' => false,
                'options' => [
                    [
                        'name' => 'XL',
                        'prices' => [PriceDto::from(Money::of(0, $this->currency->value))],
                    ],
                    [
                        'name' => 'L',
                        'prices' => [PriceDto::from(Money::of(100, $this->currency->value))],
                    ],
                ],
            ])
        );

        $this->productWithSchema->schemas()->sync([$this->schema->getKey()]);

        $this->option = $this->schema->options->where('name', 'XL')->first();

        $item = Item::factory()->create();
        $this->option->items()->sync([$item->getKey()]);

        $this->option2 = $this->schema->options->where('name', 'L')->first();

        $this->discountRepository = App::make(DiscountRepository::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPurchaseLimitMoreAvailable($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->product->update([
            'purchase_limit_per_user' => 5,
        ]);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => '9200.00',
                'cart_total' => '9200.00',
                'shipping_price_initial' => '0.00',
                'shipping_price' => '0.00',
                'summary' => '9200.00',
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => '4600.00',
                'price_discounted' => '4600.00',
                'quantity' => 2,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPurchaseLimitLessAvailable($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->product->update([
            'purchase_limit_per_user' => 1,
        ]);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => '4600.00',
                'cart_total' => '4600.00',
                'shipping_price_initial' => '0.00',
                'shipping_price' => '0.00',
                'summary' => '4600.00',
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => '4600.00',
                'price_discounted' => '4600.00',
            ]);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     */
    public function testCartProcessPurchaseLimitWithSale($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $sale = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'name' => 'Sale for limited product',
            'target_is_allow_list' => true,
            'percentage' => null,
        ]);

        $this->discountRepository->setDiscountAmounts($sale->getKey(), [
            PriceDto::from([
                'value' => '300.00',
                'currency' => $this->currency,
            ])
        ]);

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN,
            'value' => [
                'products' => [
                    $this->product->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $sale->conditionGroups()->attach($conditionGroup);

        $this->product->update([
            'purchase_limit_per_user' => 0,
        ]);

        $product = $this->productService->create(
            FakeDto::productCreateDto([
                'public' => true,
                'prices_base' => [PriceDto::from(Money::of(1000.0, $this->currency->value))],
            ])
        );

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
                [
                    'cartitem_id' => '2',
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => '1000.00',
                'cart_total' => '1000.00',
                'shipping_price_initial' => '0.00',
                'shipping_price' => '0.00',
                'summary' => '1000.00',
                'coupons' => [],
            ])
            ->assertJsonCount(1, 'data.items')
            ->assertJsonMissing([
                'id' => $sale->getKey(),
                'name' => $sale->name,
                'value' => '300.00',
            ])
            ->assertJsonFragment([
                'cartitem_id' => '2',
                'price' => '1000.00',
                'price_discounted' => '1000.00',
            ])
            ->assertJsonMissing([
                'cartitem_id' => '1',
                'price' => '4600.00',
                'price_discounted' => '4600.00',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPurchaseLimitAlreadyPurchased($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->product->update([
            'purchase_limit_per_user' => 1,
        ]);

        $order = Order::factory()->create([
            'paid' => true,
        ]);
        $this->{$user}->orders()->save($order);
        $order->products()->create([
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
            'price_initial' => Money::of(4600, $this->currency->value),
            'price' => Money::of(4600, $this->currency->value),
            'name' => $this->product->name,
        ]);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => '0.00',
                'cart_total' => '0.00',
                'shipping_price_initial' => '0.00',
                'shipping_price' => '0.00',
                'summary' => '0.00',
                'coupons' => [],
                'sales' => [],
                'items' => [],
            ])
            ->assertJsonCount(0, 'data.items');
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPurchaseLimitAlreadyPurchasedNotPaid($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->product->update([
            'purchase_limit_per_user' => 1,
        ]);

        $order = Order::factory()->create([
            'paid' => false,
        ]);
        $this->{$user}->orders()->save($order);
        $order->products()->create([
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
            'price_initial' => Money::of(4600, $this->currency->value),
            'price' => Money::of(4600, $this->currency->value),
            'name' => $this->product->name,
        ]);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => '4600.00',
                'cart_total' => '4600.00',
                'shipping_price_initial' => '0.00',
                'shipping_price' => '0.00',
                'summary' => '4600.00',
            ])
            ->assertJsonCount(1, 'data.items');
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPurchaseLimitSetAfterPurchase($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $order = Order::factory()->create([
            'paid' => true,
        ]);
        $this->{$user}->orders()->save($order);
        $order->products()->create([
            'product_id' => $this->product->getKey(),
            'quantity' => 2,
            'price_initial' => Money::of(4600, $this->currency->value),
            'price' => Money::of(4600, $this->currency->value),
            'name' => $this->product->name,
        ]);

        $this->product->update([
            'purchase_limit_per_user' => 1,
        ]);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => '0.00',
                'cart_total' => '0.00',
                'shipping_price_initial' => '0.00',
                'shipping_price' => '0.00',
                'summary' => '0.00',
                'coupons' => [],
                'sales' => [],
                'items' => [],
            ])
            ->assertJsonCount(0, 'data.items');
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPurchaseLimitCanceledOrder($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $status = Status::factory()->create([
            'cancel' => true,
        ]);

        $order = Order::factory()->create([
            'status_id' => $status->getKey(),
        ]);
        $this->{$user}->orders()->save($order);
        $order->products()->create([
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
            'price_initial' => Money::of(4600, $this->currency->value),
            'price' => Money::of(4600, $this->currency->value),
            'name' => $this->product->name,
        ]);

        $this->product->update([
            'purchase_limit_per_user' => 1,
        ]);

        $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => '4600.00',
                'cart_total' => '4600.00',
                'shipping_price_initial' => '0.00',
                'shipping_price' => '0.00',
                'summary' => '4600.00',
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => '4600.00',
                'price_discounted' => '4600.00',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCartProcessPurchaseLimitProductWithSchema($user): void
    {
        $this->{$user}->givePermissionTo('cart.verify');

        $this->productWithSchema->update([
            'purchase_limit_per_user' => 1,
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->productWithSchema->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
                [
                    'cartitem_id' => '2',
                    'product_id' => $this->productWithSchema->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $this->schema->getKey() => $this->option->getKey(),
                    ],
                ],
            ],
        ]);
        $response
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => '100.00',
                'cart_total' => '100.00',
                'shipping_price_initial' => '8.11',
                'shipping_price' => '8.11',
                'summary' => '108.11',
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => '100.00',
                'price_discounted' => '100.00',
            ])
            ->assertJsonMissing([
                'cartitem_id' => '2',
            ]);
    }

    public function testCartProcessPurchaseLimitNoAccount(): void
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('cart.verify');

        $this->product->update([
            'purchase_limit_per_user' => 1,
        ]);

        $this->postJson('/cart/process', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'items' => [
                [
                    'cartitem_id' => '1',
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ])
            ->assertValid()->assertOk()
            ->assertJsonFragment([
                'cart_total_initial' => '4600.00',
                'cart_total' => '4600.00',
                'shipping_price_initial' => '0.00',
                'shipping_price' => '0.00',
                'summary' => '4600.00',
                'coupons' => [],
                'sales' => [],
            ])
            ->assertJsonFragment([
                'cartitem_id' => '1',
                'price' => '4600.00',
                'price_discounted' => '4600.00',
            ]);
    }
}
