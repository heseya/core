<?php

namespace Tests\Feature;

use App\Enums\SchemaType;
use App\Enums\ShippingType;
use App\Events\ItemUpdatedQuantity;
use App\Events\OrderCreated;
use App\Events\ProductUpdated;
use App\Models\Address;
use App\Models\Deposit;
use App\Models\Item;
use App\Models\Option;
use App\Models\Order;
use App\Models\Price;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Services\AvailabilityService;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\ShippingMethodServiceContract;
use App\Services\ProductService;
use App\Services\SchemaCrudService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Heseya\Dto\DtoException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private Item $item;
    private Option $option;
    private Schema $schema;
    private Product $product;
    private ShippingMethodServiceContract $shippingMethodService;
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
        Product::query()->delete();

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $this->product = $productService->create(FakeDto::productCreateDto());
        $this->product->update([
            'available' => false,
            'public' => true,
            'quantity' => 0,
        ]);

        $this->shippingMethodService = App::make(ShippingMethodServiceContract::class);
        $this->schemaCrudService = App::make(SchemaCrudService::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testRestockAvailable(string $user): void
    {
        Event::fake(ProductUpdated::class);

        $this->{$user}->givePermissionTo('deposits.add');

        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'required' => true,
            'type' => SchemaType::SELECT,
            'available' => false,
        ]));

        $this->product->schemas()->save($schema);

        /** @var Item $item */
        $item = Item::factory()->create([
            'shipping_time' => null,
        ]);
        $item->deposits()->create([
            'quantity' => 0,
            'shipping_time' => null,
        ]);

        $option = Option::factory()->create([
            'schema_id' => $schema->getKey(),
            'available' => false,
        ]);

        $item->options()->save($option);

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/items/id:' . $item->getKey() . '/deposits', [
                'quantity' => 6,
                'shipping_time' => 0,
            ])
            ->assertCreated();

        $this
            ->assertDatabaseHas('products', [
                'id' => $this->product->getKey(),
                'quantity' => 6,
                'available' => true,
                'shipping_time' => 0,
            ])
            ->assertDatabaseHas('schemas', [
                'id' => $schema->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('options', [
                'id' => $option->getKey(),
                'available' => true,
            ]);

        $this->assertTrue($item->options->every(fn ($option) => $option->available));
        $this->assertTrue($item->options->pluck('schema')->every(fn ($schema) => $schema->available));
        $this->assertTrue($this->product->refresh()->available);

        Event::assertDispatched(ProductUpdated::class);
    }

    /**
     * @dataProvider authProvider
     * Case when options' permutations require both items' quantity to be greater than 0, restocking only 1 item.
     */
    public function testRestockUnavailable(string $user): void
    {
        $this->{$user}->givePermissionTo('deposits.add');

        Event::fake(ProductUpdated::class);

        $schemaOne = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'schemaOne',
            'type' => SchemaType::SELECT,
            'required' => true,
            'available' => false,
        ]));
        $schemaTwo = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'schemaTwo',
            'type' => SchemaType::SELECT,
            'required' => true,
            'available' => false,
        ]));

        $optionOne = Option::factory()->create([
            'schema_id' => $schemaOne->getKey(),
            'available' => false,
        ]);
        $optionTwo = Option::factory()->create([
            'schema_id' => $schemaOne->getKey(),
            'available' => false,
        ]);
        $optionThree = Option::factory()->create([
            'schema_id' => $schemaTwo->getKey(),
            'available' => false,
        ]);
        $optionFour = Option::factory()->create([
            'schema_id' => $schemaTwo->getKey(),
            'available' => false,
        ]);

        /** @var Item $itemOne */
        $itemOne = Item::factory()->create();
        $itemOne->deposits()->create([
            'quantity' => 0,
        ]);
        $itemOne->options()->saveMany([$optionOne, $optionTwo]);

        /** @var Item $itemTwo */
        $itemTwo = Item::factory()->create();
        $itemTwo->deposits()->create([
            'quantity' => 0,
        ]);
        $itemTwo->options()->saveMany([$optionThree, $optionFour]);

        $this->product->schemas()->saveMany([$schemaOne, $schemaTwo]);

        $this->actingAs($this->{$user})
            ->postJson('/items/id:' . $itemTwo->getKey() . '/deposits', [
                'quantity' => 20,
                'shipping_time' => 0,
            ])
            ->assertCreated();

        $this
            ->assertDatabaseHas('products', [
                'id' => $this->product->getKey(),
                'available' => false,
            ])
            ->assertDatabaseHas('schemas', [
                'id' => $schemaOne->getKey(),
                'available' => false,
            ])
            ->assertDatabaseHas('schemas', [
                'id' => $schemaTwo->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('options', [
                'id' => $optionOne->getKey(),
                'available' => false,
            ])
            ->assertDatabaseHas('options', [
                'id' => $optionTwo->getKey(),
                'available' => false,
            ])
            ->assertDatabaseHas('options', [
                'id' => $optionThree->getKey(),
                'available' => true,
            ])->assertDatabaseHas('options', [
                'id' => $optionFour->getKey(),
                'available' => true,
            ]);

        Event::assertNotDispatched(ProductUpdated::class);
    }

    /**
     * @dataProvider authProvider
     * Case when permutation requires single item with greater quantity.
     */
    public function testProductRequiresSingleItemWithGreaterQuantity(string $user): void
    {
        Event::fake(ProductUpdated::class);

        $this->{$user}->givePermissionTo('deposits.add');

        $data = $this->createDataPatternOne();

        $data->get('item')->options()->saveMany([$data->get('optionOne'), $data->get('optionTwo')]);

        $this->product->schemas()->saveMany([$data->get('schemaOne'), $data->get('schemaTwo')]);

        $this->actingAs($this->{$user})
            ->postJson('/items/id:' . $data->get('item')->getKey() . '/deposits', [
                'quantity' => 2,
                'shipping_time' => 0,
            ])
            ->assertCreated();

        $this->assertTrue($this->product->refresh()->available);
        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => true,
        ])
            ->assertDatabaseHas('schemas', [
                'id' => $data->get('schemaOne')->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('schemas', [
                'id' => $data->get('schemaTwo')->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('options', [
                'id' => $data->get('optionOne')->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('options', [
                'id' => $data->get('optionTwo')->getKey(),
                'available' => true,
            ]);

        Event::assertDispatched(ProductUpdated::class);
    }

    /**
     * @dataProvider authProvider
     * Case when permutation requires single item with greater quantity failed due to too small deposit.
     */
    public function testProductRequiresSingleItemWithGreaterQuantityFailed(string $user): void
    {
        Event::fake(ProductUpdated::class);

        $this->{$user}->givePermissionTo('deposits.add');

        $data = $this->createDataPatternOne();
        $data->get('item')->options()->saveMany([$data->get('optionOne'), $data->get('optionTwo')]);

        $this->product->schemas()->saveMany([$data->get('schemaOne'), $data->get('schemaTwo')]);

        $this
            ->actingAs($this->{$user})
            ->postJson('/items/id:' . $data->get('item')->getKey() . '/deposits', [
                'quantity' => 1,
                'shipping_time' => 0,
            ])
            ->assertCreated();

        $this
            ->assertDatabaseHas('products', [
                'id' => $this->product->getKey(),
                'available' => false,
                'quantity' => 0,
            ])
            ->assertDatabaseHas('schemas', [
                'id' => $data->get('schemaOne')->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('schemas', [
                'id' => $data->get('schemaTwo')->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('options', [
                'id' => $data->get('optionOne')->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('options', [
                'id' => $data->get('optionTwo')->getKey(),
                'available' => true,
            ]);

        Event::assertNotDispatched(ProductUpdated::class);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testUnavailableAfterOrder(string $user): void
    {
        Event::fake(ProductUpdated::class);

        $this->{$user}->givePermissionTo('orders.add');

        $data = $this->createDataPatternOne();

        Deposit::factory()->create([
            'item_id' => $data->get('item')->getKey(),
            'quantity' => 2,
        ]);

        $shippingMethod = $this->shippingMethodService->store(FakeDto::shippingMethodCreate([
            'shipping_type' => ShippingType::ADDRESS->value,
        ]));

        $data->get('item')->options()->saveMany([$data->get('optionOne'), $data->get('optionTwo')]);

        $this->product->schemas()->saveMany([$data->get('schemaOne'), $data->get('schemaTwo')]);
        $this->product->update(['available' => true]);

        $this->actingAs($this->{$user})->postJson('/orders', [
            'email' => 'test@test.test',
            'shipping_method_id' => $shippingMethod->getKey(),
            'shipping_place' => Address::factory()->create()->toArray(),
            'billing_address' => Address::factory()->create()->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $data->get('schemaOne')->getKey() => $data->get('optionOne')->getKey(),
                        $data->get('schemaTwo')->getKey() => $data->get('optionTwo')->getKey(),
                    ],
                ],
            ],
        ]);

        $this->assertTrue(!$this->product->refresh()->available);
        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => false,
        ])
            ->assertDatabaseHas('schemas', [
                'id' => $data->get('schemaOne')->getKey(),
                'available' => false,
            ])
            ->assertDatabaseHas('schemas', [
                'id' => $data->get('schemaTwo')->getKey(),
                'available' => false,
            ])
            ->assertDatabaseHas('options', [
                'id' => $data->get('optionOne')->getKey(),
                'available' => false,
            ])
            ->assertDatabaseHas('options', [
                'id' => $data->get('optionTwo')->getKey(),
                'available' => false,
            ]);

        Event::assertDispatched(ProductUpdated::class);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testAvailableAfterOrderCancel(string $user): void
    {
        Event::fake(ProductUpdated::class);

        $data = $this->createDataPatternOne();

        Deposit::factory()->create([
            'item_id' => $data->get('item')->getKey(),
            'quantity' => 2,
        ]);

        $data->get('item')->options()->saveMany([$data->get('optionOne'), $data->get('optionTwo')]);

        $this->product->schemas()->saveMany([$data->get('schemaOne'), $data->get('schemaTwo')]);

        $this->product->update([
            'available' => true,
            'prices' => [['value' => 0, 'currency' => Currency::DEFAULT->value]],
        ]);

        $shippingMethod = $this->shippingMethodService->store(FakeDto::shippingMethodCreate([
            'shipping_type' => ShippingType::ADDRESS->value,
        ]));

        $this->{$user}->givePermissionTo('orders.add');
        $this->{$user}->givePermissionTo('orders.edit.status');

        $response = $this->actingAs($this->{$user})->postJson('/orders', [
            'email' => 'test@test.test',
            'shipping_method_id' => $shippingMethod->getKey(),
            'shipping_place' => Address::factory()->create()->toArray(),
            'billing_address' => Address::factory()->create()->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $data->get('schemaOne')->getKey() => $data->get('optionOne')->getKey(),
                        $data->get('schemaTwo')->getKey() => $data->get('optionTwo')->getKey(),
                    ],
                ],
            ],
        ])->assertCreated();

        $order = Order::find($response->getData()->data->id);

        $statusCancel = Status::factory()->create([
            'cancel' => true,
        ]);

        $this->product->refresh();

        $this->actingAs($this->{$user})->patchJson("/orders/id:{$order->getKey()}/status", [
            'status_id' => $statusCancel->getKey(),
        ]);

        $this
            ->assertDatabaseHas('products', [
                'id' => $this->product->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('schemas', [
                'id' => $data->get('schemaOne')->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('schemas', [
                'id' => $data->get('schemaTwo')->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('options', [
                'id' => $data->get('optionOne')->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('options', [
                'id' => $data->get('optionTwo')->getKey(),
                'available' => true,
            ]);

        Event::assertDispatched(ProductUpdated::class);
    }

    public function testAvailabilityWhenProductHasDirectItems(): void
    {
        $this->prepareToCheckAvailabilityWithDirectProductItemRelation(10);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => true,
        ]);
    }

    public function testAvailabilityWhenProductHasDirectItemsFailed(): void
    {
        $this->prepareToCheckAvailabilityWithDirectProductItemRelation(9);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => false,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductAvailabilityAfterProductUpdate(string $user): void
    {
        Event::fake(ProductUpdated::class);

        $product = $this->createProductForAvailabilityCheckWithDirectItems();

        /** @var Item $item */
        $item = Item::factory()->create();
        $item->deposits()->create([
            'quantity' => 5,
        ]);

        $this->{$user}->givePermissionTo('products.edit');
        $this->actingAs($this->{$user})->patchJson('/products/id:' . $product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'prices' => [['value' => 10, 'currency' => Currency::DEFAULT->value]],
            'public' => true,
            'items' => [
                [
                    'id' => $item->getKey(),
                    'required_quantity' => 2,
                ],
            ],
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'available' => true,
        ])
            ->assertDatabaseHas('item_product', [
                'product_id' => $product->getKey(),
                'item_id' => $item->getKey(),
                'required_quantity' => 2,
            ]);

        Event::assertDispatched(ProductUpdated::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductAvailabilityAfterProductUpdateFailed(string $user): void
    {
        Event::fake(ProductUpdated::class);

        $product = $this->createProductForAvailabilityCheckWithDirectItems();

        /** @var Item $item */
        $item = Item::factory()->create();
        $item->deposits()->create([
            'quantity' => 5,
        ]);

        $this->{$user}->givePermissionTo('products.edit');
        $this->actingAs($this->{$user})->patchJson('/products/id:' . $product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'prices' => [['value' => 10, 'currency' => Currency::DEFAULT->value]],
            'public' => true,
            'items' => [
                [
                    'id' => $item->getKey(),
                    'required_quantity' => 20,
                ],
            ],
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'available' => false,
        ])
            ->assertDatabaseHas('item_product', [
                'product_id' => $product->getKey(),
                'item_id' => $item->getKey(),
                'required_quantity' => 20,
            ]);

        Event::assertDispatched(ProductUpdated::class);
    }

    public static function multipleSchemasProvider(): array
    {
        return [
            'as user three schemas' => ['user', 3],
            'as user four schemas' => ['user', 4],
            'as app three schemas' => ['application', 3],
            'as app four schemas' => ['application', 4],
        ];
    }

    /**
     * @dataProvider multipleSchemasProvider
     */
    public function testCreateOrderProductWithMultipleSchemasWithOption($user, $schemaCount): void
    {
        $this->{$user}->givePermissionTo('orders.add');
        $email = $this->faker->freeEmail;

        $currency = Currency::DEFAULT->value;
        $shippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $lowRange = PriceRange::create([
            'start' => Money::zero($currency),
            'value' => Money::of(8.11, $currency),
        ]);

        $highRange = PriceRange::create([
            'start' => Money::of(210, $currency),
            'value' => Money::zero($currency),
        ]);

        $shippingMethod->priceRanges()->saveMany([$lowRange, $highRange]);

        $address = Address::factory()->make();

        Event::fake([OrderCreated::class]);

        $schemas = $this->createSchemasWithOptions($schemaCount);

        $this->product->schemas()->sync(array_keys($schemas));
        $this->product->update([
            'has_schemas' => true,
        ]);

        $this->actingAs($this->{$user})->postJson('/orders', [
            'email' => $email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'shipping_place' => $address->toArray(),
            'billing_address' => $address->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => $schemas,
                ],
            ],
        ])
            ->assertCreated();

        Event::assertDispatched(OrderCreated::class);
    }

    /**
     * @dataProvider multipleSchemasProvider
     */
    public function testCreateWithMultipleSchemasWithOptions(string $user, int $schemaCount): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $schemas = $this->createSchemasWithOptions($schemaCount);

        $prices = array_map(fn (Currency $currency) => [
            'value' => '10.00',
            'currency' => $currency->value,
        ], Currency::cases());

        $this->actingAs($this->{$user})->postJson('/products', [
            'translations' => [
                $this->lang => ['name' => 'Test'],
            ],
            'published' => [$this->lang],
            'slug' => 'test',
            'prices_base' => $prices,
            'public' => false,
            'shipping_digital' => false,
            'sets' => [],
            'schemas' => array_keys($schemas),
        ])->assertCreated();
    }

    /**
     * @dataProvider multipleSchemasProvider
     */
    public function testAddDepositToItemInSchemaOption(string $user, int $schemaCount): void
    {
        $this->{$user}->givePermissionTo('deposits.add');

        $schemas = $this->createSchemasWithOptions($schemaCount);

        $schema = Schema::find(array_keys($schemas)[0])->with('options', 'options.items')->first();
        $item = $schema->options->first()->items->first();

        Event::fake(ItemUpdatedQuantity::class);

        $this->actingAs($this->{$user})
            ->json('POST', "/items/id:{$item->getKey()}/deposits", [
                'quantity' => 100,
                'shipping_time' => 0,
            ])
            ->assertCreated();

        Event::assertDispatched(ItemUpdatedQuantity::class);
    }

    private function createDataPatternOne(): Collection
    {
        $schemaOne = $this->schemaCrudService->store(FakeDto::schemaDto([
            'type' => SchemaType::SELECT,
            'required' => true,
        ]));

        $schemaTwo = $this->schemaCrudService->store(FakeDto::schemaDto([
            'type' => SchemaType::SELECT,
            'required' => true,
        ]));

        $optionOne = Option::factory()->create([
            'schema_id' => $schemaOne->getKey(),
            'disabled' => false,
        ]);
        $optionOne->prices()->createMany(Price::factory(['value' => 0])->prepareForCreateMany());

        $optionTwo = Option::factory()->create([
            'schema_id' => $schemaTwo->getKey(),
            'disabled' => false,
        ]);
        $optionTwo->prices()->createMany(Price::factory(['value' => 0])->prepareForCreateMany());

        /** @var Item $item */
        $item = Item::factory()->create();
        $item->deposits()->create([
            'quantity' => 0,
        ]);

        return Collection::make([
            'schemaOne' => $schemaOne,
            'schemaTwo' => $schemaTwo,
            'optionOne' => $optionOne,
            'optionTwo' => $optionTwo,
            'item' => $item,
        ]);
    }

    private function prepareToCheckAvailabilityWithDirectProductItemRelation(int $itemQuantity): void
    {
        /** @var AvailabilityService $availabilityService */
        $availabilityService = App::make(AvailabilityServiceContract::class);

        $this->product->update(['available' => false]);

        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'schemaOne',
            'type' => SchemaType::SELECT,
            'required' => true,
            'available' => false,
        ]));

        $this->product->schemas()->attach($schema->getKey());

        /** @var Item $item */
        $item = Item::factory()->create();
        $item->deposits()->create([
            'quantity' => $itemQuantity,
        ]);

        $this->product->items()->attach($item->getKey(), ['required_quantity' => 10]);

        $availabilityService->calculateProductAvailability($this->product->refresh());
    }

    /**
     * @throws RoundingNecessaryException
     * @throws DtoException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    private function createProductForAvailabilityCheckWithDirectItems(): Product
    {
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'schemaOne',
            'type' => SchemaType::SELECT,
            'required' => true,
            'available' => false,
        ]));

        $this->product->schemas()->attach($schema->getKey());

        return $this->product->refresh();
    }

    private function createSchemasWithOptions(int $schemaCount): array
    {
        $schemas = [];
        for ($i = 0; $i < $schemaCount; ++$i) {
            $item1 = Item::factory()->create();
            $item2 = Item::factory()->create();

            Deposit::factory()->create([
                'item_id' => $item1->getKey(),
                'quantity' => 10,
            ]);
            Deposit::factory()->create([
                'item_id' => $item2->getKey(),
                'quantity' => 10,
            ]);

            $schema1 = $this->schemaCrudService->store(FakeDto::schemaDto([
                'required' => true,
                'type' => SchemaType::SELECT,
                'prices' => [['value' => 10, 'currency' => Currency::DEFAULT->value]],
                'hidden' => false,
                'available' => true,
                'options' => [
                    [
                        'name' => 'A',
                        'prices' => [['value' => 10, 'currency' => Currency::DEFAULT->value]],
                        'disabled' => false,
                        'available' => true,
                        'order' => 0,
                    ] + Option::factory()->definition(),
                    [
                        'name' => 'B',
                        'prices' => [['value' => 10, 'currency' => Currency::DEFAULT->value]],
                        'disabled' => false,
                        'available' => true,
                        'order' => 2,
                    ] + Option::factory()->definition(),
                ]
            ]));

            $option1 = $schema1->options->where('name', 'A')->first();
            $option1->items()->sync([
                $item1->getKey(),
            ]);

            $option2 = $schema1->options->where('name', 'B')->first();
            $option2->items()->sync([
                $item1->getKey(),
            ]);

            $schemas[$schema1->getKey()] = $option1->getKey();
        }

        return $schemas;
    }
}
