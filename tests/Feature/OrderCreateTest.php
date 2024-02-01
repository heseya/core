<?php

namespace Tests\Feature;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\RoleType;
use App\Enums\SchemaType;
use App\Enums\ShippingType;
use App\Enums\ValidationError;
use App\Events\ItemUpdatedQuantity;
use App\Events\OrderCreated;
use App\Exceptions\PublishingException;
use App\Listeners\WebHookEventListener;
use App\Models\Address;
use App\Models\ConditionGroup;
use App\Models\Country;
use App\Models\Deposit;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Option;
use App\Models\Order;
use App\Models\Price;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use App\Models\WebHook;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\DiscountRepository;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use App\Services\SchemaCrudService;
use BenSampo\Enum\Exceptions\InvalidEnumMemberException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Language\Language;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\ProductPriceType;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\Setting\Models\Setting;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Dto\DtoException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\WebhookServer\CallWebhookJob;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class OrderCreateTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private ShippingMethod $shippingMethod;
    private ProductSet $category;
    private ProductSet $brand;
    private Address $address;
    private Product $product;
    private string $email;
    private Currency $currency;
    private Money $productPrice;
    private ProductService $productService;
    private DiscountRepository $discountRepository;
    private SchemaCrudService $schemaCrudService;
    private ProductRepository $productRepository;

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     * @throws DtoException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->discountRepository = App::make(DiscountRepository::class);

        $this->email = $this->faker->freeEmail;

        $this->productRepository = App::make(ProductRepository::class);

        $this->shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::ADDRESS,
        ]);
        /** @var PriceRange $lowRange */
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::of(8.11, Currency::DEFAULT->value),
        ]);

        /** @var PriceRange $highRange */
        $highRange = PriceRange::query()->create([
            'start' => Money::of(210, Currency::DEFAULT->value),
            'value' => Money::of(0.0, Currency::DEFAULT->value),
        ]);

        $this->shippingMethod->priceRanges()->saveMany([$lowRange, $highRange]);

        $this->address = Address::factory()->make();

        $this->product = Product::factory()->create([
            'public' => true,
        ]);

        /** @var ProductRepositoryContract $productRepository */
        $productRepository = App::make(ProductRepositoryContract::class);
        $this->currency = Currency::DEFAULT;

        $productRepository->setProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(amount: 10),
        ]);

        $prices = $productRepository->getProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE,
        ], $this->currency);

        $this->productPrice = $prices->get(ProductPriceType::PRICE_BASE->value)?->first()?->value;

        $this->productService = App::make(ProductService::class);
        $this->schemaCrudService = App::make(SchemaCrudService::class);
    }

    public function testCreateOrderUnauthorized(): void
    {
        Event::fake([OrderCreated::class]);

        $response = $this->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
        ]);

        $response->assertForbidden();
        Event::assertNotDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws MoneyMismatchException
     */
    public function testCreateSimpleOrder($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $this->productPrice = Money::of(10, $this->currency->value);

        $this->productRepository->setProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(amount: 10),
        ]);

        $productQuantity = 20;
        $salesChannelId = SalesChannel::query()->value('id');

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'sales_channel_id' => $salesChannelId,
            'currency' => $this->currency,
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'id' => $salesChannelId,
                'name' => 'Default',
            ]);
        $order = $response->getData()->data;

        $shippingPrice = $this->shippingMethod->getPrice(
            $this->productPrice->multipliedBy($productQuantity),
        );

        $summary = $this->productPrice
            ->multipliedBy($productQuantity)
            ->plus($shippingPrice);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => $this->email,
            'shipping_price' => $shippingPrice->getMinorAmount(),
            'summary' => $summary->getMinorAmount(),
            'sales_channel_id' => $salesChannelId,
            'buyer_type' => $this->{$user}->getMorphClass(),
        ]);
        $this->assertDatabaseHas('addresses', $this->address->toArray());
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $this->product->getKey(),
            'quantity' => 20,
            'vat_rate' => '0',
        ]);

        Event::assertDispatched(OrderCreated::class);

        Queue::fake();

        $order = Order::find($order->id);
        $event = new OrderCreated($order);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertNotPushed(CallWebhookJob::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderMailSend($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');
        $this->product->update([
            'price' => 10,
            'vat_rate' => 23,
        ]);

        Setting::create([
            'name' => 'admin_mails',
            'value' => 'test@example.com',
            'public' => false,
        ]);

        Mail::fake();

        $this->actingAs($this->{$user})->json('POST', '/orders', [
            'email' => $this->email,
            'currency' => $this->currency,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 20,
                ],
            ],
        ])->assertCreated();

        Mail::assertSent(\App\Mail\OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSimpleOrderWithMetadata($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $this->productRepository->setProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(amount: 10),
        ]);

        $productQuantity = 20;

        $this
            ->actingAs($this->{$user})
            ->postJson('/orders', [
                'currency' => $this->currency,
                'sales_channel_id' => SalesChannel::query()->value('id'),
                'email' => $this->email,
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'billing_address' => $this->address->toArray(),
                'shipping_place' => $this->address->toArray(),
                'items' => [
                    [
                        'product_id' => $this->product->getKey(),
                        'quantity' => $productQuantity,
                    ],
                ],
                'metadata' => [
                    'attributeMeta' => 'attributeValue',
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'metadata' => [
                    'attributeMeta' => 'attributeValue',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSimpleOrderWithMetadataPrivate($user): void
    {
        $this->{$user}->givePermissionTo(['orders.add', 'orders.show_metadata_private']);

        Event::fake([OrderCreated::class]);

        $this->productRepository->setProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(amount: 10),
        ]);

        $productQuantity = 20;

        $this
            ->actingAs($this->{$user})
            ->postJson('/orders', [
                'currency' => $this->currency,
                'sales_channel_id' => SalesChannel::query()->value('id'),
                'email' => $this->email,
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'billing_address' => $this->address->toArray(),
                'shipping_place' => $this->address->toArray(),
                'items' => [
                    [
                        'product_id' => $this->product->getKey(),
                        'quantity' => $productQuantity,
                    ],
                ],
                'metadata_private' => [
                    'attributeMetaPriv' => 'attributeValue',
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'metadata_private' => [
                    'attributeMetaPriv' => 'attributeValue',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCreateSimpleOrderPaid($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $product = $this->productService->create(
            FakeDto::productCreateDto([
                'public' => true,
                'prices_base' => [PriceDto::from(Money::zero($this->currency->value))],
            ])
        );

        $productQuantity = 1;

        $freeShipping = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::ADDRESS,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::zero(Currency::DEFAULT->value),
        ]);

        $freeShipping->priceRanges()->save($lowRange);

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $freeShipping->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
        ]);

        $response->assertCreated();
        $order = $response->getData()->data;

        $response->assertJsonFragment([
            'id' => $order->id,
            'summary' => '0.00',
            'paid' => true,
            'payable' => false,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => $this->email,
            'shipping_price' => 0,
            'summary' => 0,
            'paid' => true,
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSimpleOrderWithWebHookQueue($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        WebHook::factory()->create([
            'events' => [
                'OrderCreated',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake([OrderCreated::class]);

        $productQuantity = 20;

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address->toArray(),
            'shipping_place' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
        ]);

        $response->assertCreated();
        $order = $response->getData()->data;

        $orderTotal = $this->productPrice
            ->multipliedBy($productQuantity);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => $this->email,
            'shipping_price' => $this->shippingMethod->getPrice($orderTotal)->getMInorAmount(),
        ]);
        $this->assertDatabaseHas('addresses', $this->address->toArray());
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $this->product->getKey(),
            'quantity' => 20,
        ]);

        Event::assertDispatched(OrderCreated::class);

        Queue::fake();

        $order = Order::find($order->id);
        $event = new OrderCreated($order);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Queue::assertPushed(CallWebhookJob::class);
    }

    public function testCreateSimpleOrderWithWebHookEvent(): array
    {
        $this->user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $response = $this->actingAs($this->user)->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 20,
                ],
            ],
        ]);

        $order = Order::find($response->getData()->data->id)->with('shippingMethod')->first();

        Event::assertDispatched(OrderCreated::class);

        return [$order, new OrderCreated($order)];
    }

    public function testCreateSimpleOrderUnauthenticatedWithWebHookEvent(): array
    {
        $role = Role::where('type', RoleType::UNAUTHENTICATED)->firstOrFail();
        $role->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $response = $this->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 20,
                ],
            ],
        ]);

        $order = Order::find($response->getData()->data->id)->with('shippingMethod')->first();

        Event::assertDispatched(OrderCreated::class);

        return [$order, new OrderCreated($order)];
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrder($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 'string',
                'prices' => [['value' => 10, 'currency' => $this->currency->value]],
                'hidden' => false,
            ])
        );

        $this->product->schemas()->sync([$schema->getKey()]);

        $productQuantity = 2;

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => 'Test',
                    ],
                ],
            ],
        ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $schemaPrice = $schema->getPrice('Test', [
            $schema->getKey() => 'Test',
        ], $this->currency);

        $orderTotal = $this->productPrice
            ->plus($schemaPrice)
            ->multipliedBy($productQuantity);

        $shippingPrice = $this->shippingMethod->getPrice($orderTotal);

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'email' => $this->email,
            'shipping_price' => $this->shippingMethod->getPrice($orderTotal)->getMinorAmount(),
            'summary' => $orderTotal->plus($shippingPrice)->getMinorAmount(),
        ]);
        $this->assertDatabaseHas('addresses', $this->address->toArray());
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->getKey(),
            'product_id' => $this->product->getKey(),
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('order_schemas', [
            'order_product_id' => $order->products[0]->getKey(),
            'name' => $schema->name,
            'value' => 'Test',
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws MoneyMismatchException
     */
    public function testCreateOrderWithWebHook($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        $webHook = WebHook::factory()->create([
            'events' => [
                'ItemUpdatedQuantity',
            ],
            'model_type' => $this->{$user}::class,
            'creator_id' => $this->{$user}->getKey(),
            'with_issuer' => false,
            'with_hidden' => false,
        ]);

        Event::fake([OrderCreated::class, ItemUpdatedQuantity::class]);

        $item = Item::factory()->create();

        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 100,
        ]);

        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 'select',
                'prices_base' => [['value' => 10, 'currency' => Currency::DEFAULT->value]],
                'hidden' => false,
            ])
        );

        $option = Option::factory()->create([
            'name' => 'A',
            'disabled' => false,
            'order' => 0,
            'schema_id' => $schema->getKey(),
        ]);

        $option->prices()->createMany(
            Price::factory(['value' => 1000])->prepareForCreateMany()
        );

        $option->items()->sync([
            $item->getKey(),
        ]);

        $this->product->schemas()->sync([$schema->getKey()]);

        $productQuantity = 2;

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => $option->getKey(),
                    ],
                ],
            ],
        ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $schemaPrice = $schema->getPrice($option->getKey(), [
            $schema->getKey() => $option->getKey(),
        ], $this->currency);

        $orderTotal = $this->productPrice
            ->plus($schemaPrice)
            ->multipliedBy($productQuantity);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => $this->email,
            'shipping_price' => $this->shippingMethod->getPrice($orderTotal)->getMinorAmount(),
        ]);
        $this->assertDatabaseHas('addresses', $this->address->toArray());
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->getKey(),
            'product_id' => $this->product->getKey(),
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('order_schemas', [
            'order_product_id' => $order->products[0]->getKey(),
            'name' => $schema->name,
            'value' => $option->name,
        ]);

        Event::assertDispatched(OrderCreated::class);
        Event::assertDispatched(ItemUpdatedQuantity::class);

        Bus::fake();

        $item = Item::find($item->getKey());
        $event = new ItemUpdatedQuantity($item);
        $listener = new WebHookEventListener();

        $listener->handle($event);

        Bus::assertDispatched(CallWebhookJob::class, function ($job) use ($webHook, $item) {
            $payload = $job->payload;

            return $job->webhookUrl === $webHook->url
                && isset($job->headers['Signature'])
                && $payload['data']['id'] === $item->getKey()
                && $payload['data_type'] === 'Item'
                && $payload['event'] === 'ItemUpdatedQuantity';
        });
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderHiddenSchema($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 'string',
                'prices' => [['value' => 10, 'currency' => $this->currency->value]],
                'hidden' => true,
            ])
        );

        $this->product->schemas()->sync([$schema->getKey()]);

        $productQuantity = 2;

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => 'Test',
                    ],
                ],
            ],
        ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $schemaPrice = $schema->getPrice('Test', [
            $schema->getKey() => 'Test',
        ], $this->currency);

        $orderTotal = $this->productPrice
            ->plus($schemaPrice)
            ->multipliedBy($productQuantity);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => $this->email,
            'shipping_price' => $this->shippingMethod->getPrice($orderTotal)->getMinorAmount(),
        ]);
        $this->assertDatabaseHas('addresses', $this->address->toArray());
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->getKey(),
            'product_id' => $this->product->getKey(),
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('order_schemas', [
            'order_product_id' => $order->products[0]->getKey(),
            'name' => $schema->name,
            'value' => 'Test',
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws MathException
     * @throws MoneyMismatchException
     * @throws PublishingException
     */
    public function testCreateOrderNonRequiredSchemaEmpty($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $schemaPrice = 10;
        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => SchemaType::STRING->name,
                'prices' => [['value' => $schemaPrice, 'currency' => Currency::DEFAULT->value]],
                'required' => false, // Important!
            ])
        );

        $this->product->schemas()->sync([$schema->getKey()]);

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => 'test@example.com',
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $schema->getKey() => '',
                    ],
                ],
            ],
        ]);

        $response->assertCreated();

        /** @var Order $order */
        $order = Order::findOrFail(
            $response->json('data.id'),
        );

        // Expected price doesn't include empty schema
        $expectedOrderPrice = $this->productPrice->plus(
            $this->shippingMethod->getPrice($this->productPrice),
        );
        $this->assertEquals($expectedOrderPrice, $order->summary);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderNoSalesIds($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => null,
            'percentage' => '10',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);
        $shippingMethod = ShippingMethod::factory()->create([
            'shipping_type' => ShippingType::ADDRESS,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::zero(Currency::DEFAULT->value),
        ]);
        $shippingMethod->priceRanges()->save($lowRange);
        $discount->products()->attach($this->product->getKey());

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => [
                'end_at' => Carbon::yesterday(),
                'is_in_range' => true,
            ],
        ]);

        $discount->conditionGroups()->attach($conditionGroup);

        $response = $this->actingAs($this->{$user})->json('POST', '/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'billing_address' => $this->address->toArray(),
            'shipping_place' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertCreated();

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderWithDiscount($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'percentage' => '10',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);
        $shippingMethod = ShippingMethod::factory()->create([
            'shipping_type' => ShippingType::ADDRESS,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::zero(Currency::DEFAULT->value),
        ]);
        $shippingMethod->priceRanges()->save($lowRange);
        $discount->products()->attach($this->product->getKey());

        $response = $this->actingAs($this->{$user})->json('POST', '/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
            'coupons' => [
                $discount->code,
            ],
        ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $orderTotal = $this->productPrice;
        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'email' => $this->email,
            'shipping_price' => $shippingMethod->getPrice($orderTotal)->getMinorAmount(),
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderWithDiscountOrder($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'percentage' => '10',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ]);
        $shippingMethod = ShippingMethod::factory()->create([
            'shipping_type' => ShippingType::ADDRESS,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::zero(Currency::DEFAULT->value),
        ]);
        $shippingMethod->priceRanges()->save($lowRange);
        $discount->products()->attach($this->product->getKey());

        $response = $this->actingAs($this->{$user})->json('POST', '/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
            'coupons' => [
                $discount->code,
            ],
        ])
            ->assertCreated()
            ->assertJsonFragment([
                'discounts' => [
                    [
                        'discount' => [
                            'id' => $discount->getKey(),
                            'name' => $discount->name,
                            'slug' => $discount->slug,
                            'description' => $discount->description,
                            'amounts' => null,
                            'target_type' => DiscountTargetType::ORDER_VALUE,
                            'percentage' => '10.0000',
                            'priority' => $discount->priority,
                            'uses' => 1,
                            'target_is_allow_list' => true,
                            'active' => true,
                            'description_html' => '',
                            'published' => [
                                $this->lang,
                            ],
                            'metadata' => [],
                            'code' => $discount->code,
                        ],
                        'code' => $discount->code,
                        'name' => $discount->name,
                        'amount' => null,
                        'target_type' => DiscountTargetType::ORDER_VALUE,
                        'percentage' => '10.0000',
                        'applied_discount' => '1.00',
                    ],
                ],
            ]);
        $order = Order::find($response->getData()->data->id);

        $orderTotal = $this->productPrice;
        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'email' => $this->email,
            'shipping_price' => $shippingMethod->getPrice($orderTotal)->getMinorAmount(),
        ]);

        $this->assertDatabaseHas('order_discounts', [
            'model_type' => $order->getMorphClass(),
            'model_id' => $order->getKey(),
            'discount_id' => $discount->getKey(),
            'code' => $discount->code,
            'currency' => $this->currency,
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCreateOrderWithDiscountMinimalPrices($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $product = $this->productService->create(
            FakeDto::productCreateDto([
                'public' => true,
                'prices_base' => [PriceDto::from(Money::of(150, $this->currency->value))],
            ])
        );

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'percentage' => '95',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        $saleOrder = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'name' => 'Kupon order',
            'percentage' => null,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        $amounts = array_map(fn (Currency $currency) => PriceDto::fromMoney(
            Money::of(50, $currency->value),
        ), Currency::cases());

        $this->discountRepository::setDiscountAmounts($saleOrder->getKey(), $amounts);

        $couponShipping = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'name' => 'Kupon shipping',
            'percentage' => null,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => false,
        ]);

        $amounts = array_map(fn (Currency $currency) => PriceDto::fromMoney(
            Money::of(15, $currency->value),
        ), Currency::cases());

        $this->discountRepository::setDiscountAmounts($couponShipping->getKey(), $amounts);

        $discount->products()->attach($product->getKey());

        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::ADDRESS,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::of(10, Currency::DEFAULT->value),
        ]);

        $shippingMethod->priceRanges()->saveMany([$lowRange]);

        $response = $this->actingAs($this->{$user})->json('POST', '/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'billing_address' => $this->address->toArray(),
            'shipping_place' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $product->getKey(),
                    'quantity' => 1,
                ],
            ],
            'coupons' => [
                $discount->code,
                $couponShipping->code,
            ],
        ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'cart_total_initial' => '150.00',
                'cart_total' => '0.01',
                'shipping_price_initial' => '10.00',
                'shipping_price' => '0.01',
                'summary' => '0.02',
            ]);
        $order = Order::find($response->getData()->data->id);

        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->getKey(),
            'product_id' => $product->getKey(),
            'price_initial' => '15000',
            'price' => '750',
        ]);

        $this->assertDatabaseHas('order_discounts', [
            'discount_id' => $discount->getKey(),
            'applied' => '14250', // -95%
            'currency' => $this->currency,
        ]);

        $this->assertDatabaseHas('order_discounts', [
            'model_id' => $order->getKey(),
            'discount_id' => $saleOrder->getKey(),
            'applied' => '749', // discount -50, but price should be 7.49 when discount is applied
            'currency' => $this->currency,
        ]);

        $this->assertDatabaseHas('order_discounts', [
            'model_id' => $order->getKey(),
            'discount_id' => $couponShipping->getKey(),
            'applied' => '999', // discount -15, but shipping_price_initial is 9.99
            'currency' => $this->currency,
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantCreateOrderWithoutItems($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        $shippingMethod = ShippingMethod::factory()->create([
            'shipping_type' => ShippingType::ADDRESS,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::zero(Currency::DEFAULT->value),
        ]);
        $shippingMethod->priceRanges()->save($lowRange);

        $response = $this->actingAs($this->{$user})->json('POST', '/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'shipping_place' => [
                'name' => 'Wojtek Testowy',
                'phone' => '+48123321123',
                'address' => 'Gdańska 89/1',
                'zip' => '12-123',
                'city' => 'Bydgoszcz',
                'country' => 'PL',
            ],
            'items' => [],
        ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantCreateOrderWithoutBillingAddress($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $this->productRepository->setProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(amount: 10),
        ]);

        $this
            ->actingAs($this->{$user})
            ->postJson(
                '/orders',
                [
                    'currency' => $this->currency,
                    'sales_channel_id' => SalesChannel::query()->value('id'),
                    'email' => $this->email,
                    'shipping_method_id' => $this->shippingMethod->getKey(),
                    'shipping_address' => $this->address->toArray(),
                    'items' => [
                        [
                            'product_id' => $this->product->getKey(),
                            'quantity' => 20,
                        ],
                    ],
                ],
            )
            ->assertUnprocessable();

        Event::assertNotDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantCreateOrderWithExpiredDiscount($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'percentage' => '10',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ]);

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => [
                'end_at' => Carbon::yesterday(),
                'is_in_range' => true,
            ],
        ]);

        $discount->conditionGroups()->attach($conditionGroup);

        $shippingMethod = ShippingMethod::factory()->create([
            'shipping_type' => ShippingType::ADDRESS,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::zero(Currency::DEFAULT->value),
        ]);
        $shippingMethod->priceRanges()->save($lowRange);

        $response = $this->actingAs($this->{$user})->json('POST', '/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'shipping_address' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
            'coupons' => [
                $discount->code,
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantCreateOrderWithDiscountBeforeStart($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'percentage' => '10',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ]);

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => [
                'start_at' => Carbon::tomorrow(),
                'is_in_range' => true,
            ],
        ]);

        $discount->conditionGroups()->attach($conditionGroup);

        $shippingMethod = ShippingMethod::factory()->create([
            'shipping_type' => ShippingType::ADDRESS,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::zero(Currency::DEFAULT->value),
        ]);
        $shippingMethod->priceRanges()->save($lowRange);

        $response = $this->actingAs($this->{$user})->json('POST', '/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'shipping_address' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
            'coupons' => [
                $discount->code,
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderWithShippingMethodTypeAddress($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 'string',
                'prices' => [['value' => 10, 'currency' => $this->currency->value]],
                'hidden' => false,
            ])
        );

        $this->product->schemas()->sync([$schema->getKey()]);

        $this->productRepository->setProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(amount: 100),
        ]);

        $productQuantity = 2;

        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::ADDRESS,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::zero(Currency::DEFAULT->value),
        ]);
        $shippingMethod->priceRanges()->save($lowRange);

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'invoice_requested' => true,
            'shipping_place' => $this->address,
            'billing_address' => Address::factory()->create(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => 'Test',
                    ],
                ],
            ],
        ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'invoice_requested' => true,
            'shipping_place' => null,
            'shipping_address_id' => $order->shippingAddress->getKey(),
            'shipping_type' => ShippingType::ADDRESS->value,
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderWithShippingMethodTypePoint($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 'string',
                'prices' => [['value' => 10, 'currency' => $this->currency->value]],
                'hidden' => false,
            ])
        );

        $this->product->schemas()->sync([$schema->getKey()]);
        $this->product->update([
            'price' => 100,
        ]);

        $productQuantity = 2;

        $pointAddress = Address::factory()->create();

        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::POINT,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::zero(Currency::DEFAULT->value),
        ]);
        $shippingMethod->priceRanges()->save($lowRange);

        $shippingMethod->shippingPoints()->attach($pointAddress);

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'invoice_requested' => true,
            'shipping_place' => $pointAddress->getKey(),
            'billing_address' => Address::factory()->create(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => 'Test',
                    ],
                ],
            ],
        ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'invoice_requested' => true,
            'shipping_place' => null,
            'shipping_address_id' => $pointAddress->getKey(),
            'shipping_type' => ShippingType::POINT->value,
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderWithShippingMethodTypePointExternal($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 'string',
                'prices' => [['value' => 10, 'currency' => $this->currency->value]],
                'hidden' => false,
            ])
        );

        $this->product->schemas()->sync([$schema->getKey()]);
        $this->product->update([
            'price' => 100,
        ]);

        $productQuantity = 2;

        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::POINT_EXTERNAL,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::zero(Currency::DEFAULT->value),
        ]);
        $shippingMethod->priceRanges()->save($lowRange);

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'invoice_requested' => true,
            'shipping_place' => 'Testowy numer domu w testowym mieście',
            'billing_address' => Address::factory()->create(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => 'Test',
                    ],
                ],
            ],
        ]);

        $response->assertCreated();
        $order = Order::find($response->getData()->data->id);

        $this->assertDatabaseHas('orders', [
            'id' => $order->getKey(),
            'invoice_requested' => true,
            'shipping_address_id' => null,
            'shipping_place' => 'Testowy numer domu w testowym mieście',
            'shipping_type' => ShippingType::POINT_EXTERNAL->value,
        ]);

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderWithMissingShippingAddress($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 'string',
                'prices' => [['value' => 10, 'currency' => $this->currency->value]],
                'hidden' => false,
            ])
        );

        $this->product->schemas()->sync([$schema->getKey()]);
        $this->product->update([
            'price' => 100,
        ]);

        $productQuantity = 2;

        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::POINT,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::zero(Currency::DEFAULT->value),
        ]);
        $shippingMethod->priceRanges()->save($lowRange);

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'invoice_requested' => true,
            'billing_address' => Address::factory()->create(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => 'Test',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(422);

        Event::assertNotDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderWithMissingShippingPlace($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 'string',
                'prices' => [['value' => 10, 'currency' => $this->currency->value]],
                'hidden' => false,
            ])
        );

        $this->product->schemas()->sync([$schema->getKey()]);
        $this->product->update([
            'price' => 100,
        ]);

        $productQuantity = 2;

        $shippingMethod = ShippingMethod::factory()->create([
            'public' => true,
            'shipping_type' => ShippingType::POINT_EXTERNAL,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::zero(Currency::DEFAULT->value),
        ]);
        $shippingMethod->priceRanges()->save($lowRange);

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'invoice_requested' => true,
            'billing_address' => Address::factory()->create(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => 'Test',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(422);

        Event::assertNotDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantCreateOrderWithInactiveCoupon($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'percentage' => '10',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
            'active' => false,
        ]);

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => [
                'end_at' => Carbon::tomorrow(),
                'is_in_range' => true,
            ],
        ]);

        $discount->conditionGroups()->attach($conditionGroup);

        $shippingMethod = ShippingMethod::factory()->create([
            'shipping_type' => ShippingType::ADDRESS,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::zero(Currency::DEFAULT->value),
        ]);
        $shippingMethod->priceRanges()->save($lowRange);

        $response = $this->actingAs($this->{$user})->json('POST', '/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
            'coupons' => [
                $discount->code,
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCantCreateOrderWithInactiveSale($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        $discount = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => null,
            'percentage' => '10',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
            'active' => false,
        ]);

        $conditionGroup = ConditionGroup::create();

        $conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => [
                'end_at' => Carbon::tomorrow(),
                'is_in_range' => true,
            ],
        ]);

        $discount->conditionGroups()->attach($conditionGroup);

        $shippingMethod = ShippingMethod::factory()->create([
            'shipping_type' => ShippingType::ADDRESS,
        ]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero(Currency::DEFAULT->value),
            'value' => Money::zero(Currency::DEFAULT->value),
        ]);
        $shippingMethod->priceRanges()->save($lowRange);

        $response = $this->actingAs($this->{$user})->json('POST', '/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'delivery_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
            'sale_ids' => [
                $discount->getKey(),
            ],
        ]);

        $response->assertStatus(422);
    }

    public function testCreateOrderWithoutAnyStatuses(): void
    {
        Status::query()->delete();

        $this->user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $response = $this->actingAs($this->user)->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address->toArray(),
            'shipping_place' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
        ]);

        $response
            ->assertStatus(500)
            ->assertJsonFragment(['message' => Exceptions::SERVER_ORDER_STATUSES_NOT_CONFIGURED]);

        Event::assertNotDispatched(OrderCreated::class);
    }

    public function testCreateOrderWithEmptyVat(): void
    {
        $this->user->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $address = [
            'name' => 'Johny Mielony',
            'address' => 'Street 89',
            'zip' => '80-200',
            'city' => 'City',
            'country' => 'PL',
            'phone' => '+48543234123',
        ];

        $this
            ->actingAs($this->user)
            ->postJson('/orders', [
                'currency' => $this->currency,
                'sales_channel_id' => SalesChannel::query()->value('id'),
                'email' => $this->email,
                'shipping_method_id' => $this->shippingMethod->getKey(),
                'billing_address' => $address + [
                        'vat' => null,
                    ],
                'shipping_place' => $address + [
                        'vat' => null,
                    ],
                'items' => [
                    [
                        'product_id' => $this->product->getKey(),
                        'quantity' => 20,
                    ],
                ],
            ])
            ->assertCreated();

        $this->assertDatabaseHas(
            'addresses',
            $address + [
                'vat' => null,
            ]
        );
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderPurchaseLimit($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $this->product->update([
            'price' => 10,
            'purchase_limit_per_user' => 10,
        ]);

        $productQuantity = 20;

        $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address->toArray(),
            'shipping_place' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
        ])->assertUnprocessable()
            ->assertJsonFragment([
                'message' => Exceptions::PRODUCT_PURCHASE_LIMIT,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderPurchaseLimitAlreadyPurchased($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $this->product->update([
            'price' => 10,
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

        $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address->toArray(),
            'shipping_place' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => Exceptions::PRODUCT_PURCHASE_LIMIT,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderPurchaseLimitSetAfterPurchase($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

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

        $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address->toArray(),
            'shipping_place' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => Exceptions::PRODUCT_PURCHASE_LIMIT,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderPurchaseLimitSetAfterPurchaseNotPaid($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $order = Order::factory()->create([
            'paid' => false,
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

        $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address->toArray(),
            'shipping_place' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
        ])
            ->assertCreated();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderPurchaseLimitCanceledOrder($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

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

        $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'billing_address' => $this->address->toArray(),
            'shipping_place' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                ],
            ],
        ])->assertCreated();
    }

    /**
     * @dataProvider authProvider
     *
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws MoneyMismatchException
     */
    public function testCreateSimpleOrderWithWrongCountry($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $this->productPrice = Money::of(10, $this->currency->value);

        $this->productRepository->setProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(amount: 10),
        ]);

        $productQuantity = 20;

        $country = Country::query()->firstOrCreate([
            'code' => 'PL',
        ], [
            'name' => 'Poland',
        ]);

        $this->shippingMethod->countries()->attach($country);
        $this->shippingMethod->is_block_list_countries = false;
        $this->shippingMethod->save();

        $this->address->country = $country->code;
        $this->address->save();

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
            'currency' => Currency::DEFAULT->value,
        ]);

        $response->assertValid()->assertCreated();

        Event::assertDispatchedTimes(OrderCreated::class, 1);

        $country2 = Country::query()->firstOrCreate([
            'code' => 'DE',
        ], [
            'name' => 'Germany',
        ]);
        $address2 = Address::factory()->create([
            'country' => $country2->code,
        ]);

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $address2->toArray(),
            'billing_address' => $address2->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
            'currency' => Currency::DEFAULT->value,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonFragment(['message' => Exceptions::CLIENT_SHIPPING_METHOD_INVALID_COUNTRY->value]);

        Event::assertDispatchedTimes(OrderCreated::class, 1); // no increase
    }

    /**
     * @dataProvider authProvider
     *
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws MoneyMismatchException
     */
    public function testCreateOrderLanguage($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $this->productPrice = Money::of(10, $this->currency->value);

        $this->productRepository->setProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(amount: 10),
        ]);

        $productQuantity = 20;
        $salesChannelId = SalesChannel::query()->value('id');

        /** @var Language $en */
        $en = Language::firstOrCreate([
            'iso' => 'en',
        ], [
            'name' => 'English',
            'default' => false,
        ]);

        $plName = $this->product->name;
        $this->product->setLocale($en->getKey())->fill([
            'name' => 'English name',
        ]);
        $this->product->update([
            'published' => [$this->lang, $en->getKey()],
        ]);
        $this->product->save();

        $response = $this->actingAs($this->{$user})->json('POST', '/orders', [
            'sales_channel_id' => $salesChannelId,
            'currency' => $this->currency,
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
            'language' => 'en',
        ], [
            'Accept-Language' => 'en, pl, es',
        ])
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'English name',
                'quantity' => $productQuantity,
            ])
            ->assertJsonMissing([
                'name' => $plName,
            ]);

        $order = $response->getData()->data;

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => $this->email,
            'sales_channel_id' => $salesChannelId,
            'language' => 'en',
        ]);

        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'name' => 'English name',
        ]);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws MoneyMismatchException
     */
    public function testCreateOrderDefaultLang($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $this->productPrice = Money::of(10, $this->currency->value);

        $this->productRepository->setProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(amount: 10),
        ]);

        $productQuantity = 20;
        $salesChannelId = SalesChannel::query()->value('id');

        $language = Language::query()->where('default', true)->firstOrFail()->iso;

        $response = $this->actingAs($this->{$user})->json('POST', '/orders', [
            'sales_channel_id' => $salesChannelId,
            'currency' => $this->currency,
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
            'language' => $language,
        ])
            ->assertCreated()
            ->assertJsonFragment([
                'name' => $this->product->name,
            ]);

        $order = $response->getData()->data;

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => $this->email,
            'sales_channel_id' => $salesChannelId,
            'language' => $language,
        ]);

        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'name' => $this->product->name,
        ]);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws MoneyMismatchException
     */
    public function testCreateOrderLanguageDefaultProductName($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        Event::fake([OrderCreated::class]);

        $this->productPrice = Money::of(10, $this->currency->value);

        $this->productRepository->setProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(amount: 10),
        ]);

        $productQuantity = 20;
        $salesChannelId = SalesChannel::query()->value('id');

        /** @var Language $en */
        $en = Language::firstOrCreate([
            'iso' => 'en',
        ], [
            'name' => 'English',
            'default' => false,
        ]);

        $plName = $this->product->name;;

        $response = $this->actingAs($this->{$user})->json('POST', '/orders', [
            'sales_channel_id' => $salesChannelId,
            'currency' => $this->currency,
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
            'language' => 'en',
        ], [
            'Accept-Language' => 'en, pl, es',
        ])
            ->assertCreated()
            ->assertJsonFragment([
                'name' => $plName,
                'quantity' => $productQuantity,
            ]);

        $order = $response->getData()->data;

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'email' => $this->email,
            'sales_channel_id' => $salesChannelId,
            'language' => 'en',
        ]);

        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'name' => $plName,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateOrderInvalidAddress($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        $productQuantity = 2;

        $this->actingAs($this->{$user})->postJson('/orders', [
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => [
                'name' => 'Jan',
                'address' => 'Testowa',
                'phone' => '516516516',
                'zip' => '80-111',
                'city' => 'Gdańsk',
                'country' => 'PL',
            ],
            'billing_address' => [
                'name' => 'Jan',
                'address' => 'Testowa',
                'phone' => '516516516',
                'zip' => '80-111',
                'city' => 'Gdańsk',
                'country' => 'PL',
            ],
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
        ])
            ->assertJsonFragment([
                'shipping_place.name' => [[
                    'key' => ValidationError::FULLNAME->value,
                    'message' => Exceptions::CLIENT_FULL_NAME->value,
                ]]
            ])
            ->assertJsonFragment([
                'shipping_place.address' => [[
                    'key' => ValidationError::STREETNUMBER->value,
                    'message' => Exceptions::CLIENT_STREET_NUMBER->value,
                ]]
            ])
            ->assertJsonFragment([
                'billing_address.name' => [[
                    'key' => ValidationError::FULLNAME->value,
                    'message' => Exceptions::CLIENT_FULL_NAME->value,
                ]]
            ])
            ->assertJsonFragment([
                'billing_address.address' => [[
                    'key' => ValidationError::STREETNUMBER->value,
                    'message' => Exceptions::CLIENT_STREET_NUMBER->value,
                ]]
            ]);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws MoneyMismatchException
     */
    public function testCreateInvalidSchema($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        $this->productPrice = Money::of(10, $this->currency->value);

        $this->productRepository->setProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(amount: 10),
        ]);

        $productQuantity = 20;
        $salesChannelId = SalesChannel::query()->value('id');

        $schemaID = Str::uuid()->toString();
        $this->actingAs($this->{$user})->postJson('/orders', [
            'sales_channel_id' => $salesChannelId,
            'currency' => $this->currency,
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schemaID => Str::uuid()->toString(),
                    ]
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => Exceptions::CLIENT_SCHEMA_INVALID->value . ': ' . $schemaID,
            ]);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws MoneyMismatchException
     */
    public function testCreateInvalidSchemaOption($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        $this->productPrice = Money::of(10, $this->currency->value);

        $this->productRepository->setProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(amount: 10),
        ]);

        $productQuantity = 20;
        $salesChannelId = SalesChannel::query()->value('id');

        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => SchemaType::SELECT,
                'prices' => [['value' => 10, 'currency' => $this->currency->value]],
                'hidden' => false,
            ])
        );

        $schema2 = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => SchemaType::SELECT,
                'prices' => [['value' => 10, 'currency' => $this->currency->value]],
                'hidden' => false,
            ])
        );

        $this->product->schemas()->sync([$schema->getKey(), $schema2->getKey()]);

        $optionId = Str::uuid()->toString();
        $this->actingAs($this->{$user})->postJson('/orders', [
            'sales_channel_id' => $salesChannelId,
            'currency' => $this->currency,
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                    'schemas' => [
                        $schema->getKey() => $optionId,
                    ]
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => Exceptions::CLIENT_SCHEMA_OPTIONS_INVALID->value . ': ' . $optionId,
            ]);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws MoneyMismatchException
     */
    public function testCreateNoRequiredSchema($user): void
    {
        $this->{$user}->givePermissionTo('orders.add');

        $this->productPrice = Money::of(10, $this->currency->value);

        $this->productRepository->setProductPrices($this->product->getKey(), [
            ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(amount: 10),
        ]);

        $productQuantity = 20;
        $salesChannelId = SalesChannel::query()->value('id');

        $schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => SchemaType::SELECT,
                'prices' => [['value' => 10, 'currency' => $this->currency->value]],
                'hidden' => false,
                'required' => true,
            ])
        );

        Option::factory()->create([
            'name' => 'A',
            'disabled' => false,
            'order' => 0,
            'schema_id' => $schema->getKey(),
        ]);

        $this->product->schemas()->sync([$schema->getKey()]);

        $this->actingAs($this->{$user})->postJson('/orders', [
            'sales_channel_id' => $salesChannelId,
            'currency' => $this->currency,
            'email' => $this->email,
            'shipping_method_id' => $this->shippingMethod->getKey(),
            'shipping_place' => $this->address->toArray(),
            'billing_address' => $this->address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => $productQuantity,
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => Exceptions::CLIENT_PRODUCT_OPTION->name,
                'message' => Exceptions::CLIENT_PRODUCT_OPTION->value,
            ]);
    }
}
