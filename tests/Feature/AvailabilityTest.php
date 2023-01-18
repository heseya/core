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
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Services\AvailabilityService;
use App\Services\Contracts\AvailabilityServiceContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private Item $item;
    private Option $option;
    private Schema $schema;
    private Product $product;

    public function setUp(): void
    {
        parent::setUp();
        Product::query()->delete();
        $this->product = Product::factory()->create([
            'available' => false,
            'public' => true,
            'quantity' => 0,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testRestockAvailable($user): void
    {
        Event::fake(ProductUpdated::class);

        $this->$user->givePermissionTo('deposits.add');

        $schema = Schema::factory()->create([
            'required' => true,
            'type' => SchemaType::SELECT,
            'available' => false,
        ]);

        $this->product->schemas()->save($schema);

        /** @var Item $item */
        $item = Item::factory()->create();
        $item->deposits()->create([
            'quantity' => 0,
        ]);

        $option = Option::factory()->create([
            'schema_id' => $schema->getKey(),
            'available' => false,
        ]);

        $item->options()->save($option);

        $this->actingAs($this->$user)->postJson('/items/id:' . $item->getKey() . '/deposits', [
            'quantity' => 6,
        ]);

        $this
            ->assertDatabaseHas('products', [
                'id' => $this->product->getKey(),
                'quantity' => 6,
                'available' => true,
                'shipping_time' => null,
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
    public function testRestockUnavailable($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        Event::fake(ProductUpdated::class);

        $schemaOne = Schema::factory()->create([
            'name' => 'schemaOne',
            'type' => SchemaType::SELECT,
            'required' => true,
            'available' => false,
        ]);
        $schemaTwo = Schema::factory()->create([
            'name' => 'schemaTwo',
            'type' => SchemaType::SELECT,
            'required' => true,
            'available' => false,
        ]);

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

        $this->actingAs($this->$user)->postJson('/items/id:' . $itemTwo->getKey() . '/deposits', [
            'quantity' => 20,
        ]);

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
    public function testProductRequiresSingleItemWithGreaterQuantity($user): void
    {
        Event::fake(ProductUpdated::class);

        $this->$user->givePermissionTo('deposits.add');

        $data = $this->createDataPatternOne();

        $data->get('item')->options()->saveMany([$data->get('optionOne'), $data->get('optionTwo')]);

        $this->product->schemas()->saveMany([$data->get('schemaOne'), $data->get('schemaTwo')]);

        $this->actingAs($this->$user)->postJson('/items/id:' . $data->get('item')->getKey() . '/deposits', [
            'quantity' => 2,
        ]);

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
    public function testProductRequiresSingleItemWithGreaterQuantityFailed($user): void
    {
        Event::fake(ProductUpdated::class);

        $this->$user->givePermissionTo('deposits.add');

        $data = $this->createDataPatternOne();
        $data->get('item')->options()->saveMany([$data->get('optionOne'), $data->get('optionTwo')]);

        $this->product->schemas()->saveMany([$data->get('schemaOne'), $data->get('schemaTwo')]);

        $this
            ->actingAs($this->$user)
            ->postJson('/items/id:' . $data->get('item')->getKey() . '/deposits', [
                'quantity' => 1,
            ]);

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
     */
    public function testUnavailableAfterOrder($user): void
    {
        Event::fake(ProductUpdated::class);

        $this->$user->givePermissionTo('orders.add');

        $data = $this->createDataPatternOne();

        Deposit::factory()->create([
            'item_id' => $data->get('item')->getKey(),
            'quantity' => 2,
        ]);

        $data->get('item')->options()->saveMany([$data->get('optionOne'), $data->get('optionTwo')]);

        $this->product->schemas()->saveMany([$data->get('schemaOne'), $data->get('schemaTwo')]);
        $this->product->update(['available' => true]);

        $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'test@test.test',
            'shipping_method_id' => ShippingMethod::factory()->create([
                'shipping_type' => ShippingType::ADDRESS,
            ])->getKey(),
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
     */
    public function testAvailableAfterOrderCancel($user): void
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
            'price' => 0,
        ]);

        $this->$user->givePermissionTo('orders.add');
        $this->$user->givePermissionTo('orders.edit.status');

        $response = $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'test@test.test',
            'shipping_method_id' => ShippingMethod::factory()->create([
                'shipping_type' => ShippingType::ADDRESS,
            ])->getKey(),
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

        $order = Order::find($response->getData()->data->id);

        $statusCancel = Status::factory()->create([
            'cancel' => true,
        ]);

        $this->product->refresh();

        $this->actingAs($this->$user)->patchJson("/orders/id:{$order->getKey()}/status", [
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
    public function testProductAvailabilityAfterProductUpdate($user): void
    {
        Event::fake(ProductUpdated::class);

        $product = $this->createProductForAvailabilityCheckWithDirectItems();

        /** @var Item $item */
        $item = Item::factory()->create();
        $item->deposits()->create([
            'quantity' => 5,
        ]);

        $this->$user->givePermissionTo('products.edit');
        $this->actingAs($this->$user)->patchJson('/products/id:' . $product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'price' => 10,
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
    public function testProductAvailabilityAfterProductUpdateFailed($user): void
    {
        Event::fake(ProductUpdated::class);

        $product = $this->createProductForAvailabilityCheckWithDirectItems();

        /** @var Item $item */
        $item = Item::factory()->create();
        $item->deposits()->create([
            'quantity' => 5,
        ]);

        $this->$user->givePermissionTo('products.edit');
        $this->actingAs($this->$user)->patchJson('/products/id:' . $product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'price' => 10,
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

    public function multipleSchemasProvider(): array
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
        $this->$user->givePermissionTo('orders.add');
        $email = $this->faker->freeEmail;

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $shippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $lowRange = PriceRange::create(['start' => 0]);
        $lowRange->prices()->create(['value' => 8.11]);

        $highRange = PriceRange::create(['start' => 210]);
        $highRange->prices()->create(['value' => 0.0]);

        $shippingMethod->priceRanges()->saveMany([$lowRange, $highRange]);

        $address = Address::factory()->make();

        Event::fake([OrderCreated::class]);

        $schemas = $this->createSchemasWithOptions($schemaCount);

        $product->schemas()->sync(array_keys($schemas));
        $product->update([
            'has_schemas' => true,
            'price' => 100,
        ]);

        $this->actingAs($this->$user)->postJson('/orders', [
            'email' => $email,
            'shipping_method_id' => $shippingMethod->getKey(),
            'shipping_place' => $address->toArray(),
            'billing_address' => $address->toArray(),
            'items' => [
                [
                    'product_id' => $product->getKey(),
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
    public function testCreateWithMultipleSchemasWithOptions($user, int $schemaCount): void
    {
        $this->$user->givePermissionTo('products.add');

        $schemas = $this->createSchemasWithOptions($schemaCount);

        $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 10,
            'public' => false,
            'shipping_digital' => false,
            'sets' => [],
            'schemas' => array_keys($schemas),
        ])->assertCreated();
    }

    /**
     * @dataProvider multipleSchemasProvider
     */
    public function testAddDepositToItemInSchemaOption($user, int $schemaCount): void
    {
        $this->$user->givePermissionTo('deposits.add');

        $schemas = $this->createSchemasWithOptions($schemaCount);

        $schema = Schema::find(array_keys($schemas)[0])->with('options', 'options.items')->first();
        $item = $schema->options->first()->items->first();

        Event::fake(ItemUpdatedQuantity::class);

        $this->actingAs($this->$user)->json('POST', "/items/id:{$item->getKey()}/deposits", [
            'quantity' => 100,
        ])->assertCreated();

        Event::assertDispatched(ItemUpdatedQuantity::class);
    }

    private function createDataPatternOne(): Collection
    {
        $schemaOne = Schema::factory()->create([
            'type' => SchemaType::SELECT,
            'required' => true,
        ]);
        $schemaTwo = Schema::factory()->create([
            'type' => SchemaType::SELECT,
            'required' => true,
        ]);

        $optionOne = Option::factory()->create([
            'schema_id' => $schemaOne->getKey(),
            'disabled' => false,
        ]);

        $optionTwo = Option::factory()->create([
            'schema_id' => $schemaTwo->getKey(),
            'disabled' => false,
        ]);

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
        $availabilityService = app(AvailabilityServiceContract::class);

        $this->product->update(['available' => false]);

        $schema = Schema::factory()->create([
            'name' => 'schemaOne',
            'type' => SchemaType::SELECT,
            'required' => true,
            'available' => false,
        ]);

        $this->product->schemas()->attach($schema->getKey());

        /** @var Item $item */
        $item = Item::factory()->create();
        $item->deposits()->create([
            'quantity' => $itemQuantity,
        ]);

        $this->product->items()->attach($item->getKey(), ['required_quantity' => 10]);

        $availabilityService->calculateProductAvailability($this->product->refresh());
    }

    private function createProductForAvailabilityCheckWithDirectItems(): Product
    {
        $product = Product::factory()->create();

        $product->update([
            'available' => false,
        ]);

        $schema = Schema::factory()->create([
            'name' => 'schemaOne',
            'type' => SchemaType::SELECT,
            'required' => true,
            'available' => false,
        ]);

        $product->schemas()->attach($schema->getKey());
        return $product->refresh();
    }

    private function createSchemasWithOptions(int $schemaCount): array
    {
        $schemas = [];
        for ($i = 0; $i < $schemaCount; $i++) {
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

            $schema1 = Schema::factory()->create([
                'required' => true,
                'type' => SchemaType::SELECT,
                'price' => 10,
                'hidden' => false,
                'available' => true,
            ]);

            $option1 = Option::factory()->create([
                'name' => 'A',
                'price' => 10,
                'disabled' => false,
                'available' => true,
                'order' => 0,
                'schema_id' => $schema1->getKey(),
            ]);

            $option1->items()->sync([
                $item1->getKey(),
            ]);

            $option2 = Option::factory()->create([
                'name' => 'B',
                'price' => 10,
                'disabled' => false,
                'available' => true,
                'order' => 2,
                'schema_id' => $schema1->getKey(),
            ]);

            $option2->items()->sync([
                $item1->getKey(),
            ]);

            $schemas[$schema1->getKey()] = $option1->getKey();
        }
        return $schemas;
    }
}
