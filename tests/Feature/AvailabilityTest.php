<?php

namespace Tests\Feature;

use App\Enums\SchemaType;
use App\Events\ProductUpdated;
use App\Models\Address;
use App\Models\Item;
use App\Models\Option;
use App\Models\Order;
use App\Models\Product;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Services\AvailabilityService;
use App\Services\Contracts\AvailabilityServiceContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Item $item;
    private Option $option;
    private Schema $schema;
    private Product $product;

    public function setUp(): void
    {
        parent::setUp();
        $this->product = Product::factory()->create([
            'available' => false,
            'public' => true,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testRestockAvailable($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        $schema = Schema::factory()->create([
            'required' => true,
            'type' => SchemaType::SELECT,
            'available' => false,
        ]);

        $this->product->schemas()->save($schema);

        $item = Item::factory()->create([
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

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => true,
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
    }

    /**
     * @dataProvider authProvider
     * Case when options' permutations require both items' quantity to be greater than 0, restocking only 1 item.
     */
    public function testRestockUnavailable($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

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

        $itemOne = Item::factory()->create([
            'quantity' => 0,
        ]);
        $itemOne->options()->saveMany([$optionOne, $optionTwo]);

        $itemTwo = Item::factory()->create([
            'quantity' => 0,
        ]);
        $itemTwo->options()->saveMany([$optionThree, $optionFour]);

        $this->product->schemas()->saveMany([$schemaOne, $schemaTwo]);

        $this->actingAs($this->$user)->postJson('/items/id:' . $itemTwo->getKey() . '/deposits', [
            'quantity' => 20,
        ]);

        $this->assertDatabaseHas('products', [
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
    }

    /**
     * @dataProvider authProvider
     * Case when permutation requires single item with greater quantity.
     */
    public function testProductRequiresSingleItemWithGreaterQuantity($user): void
    {
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
    }

    /**
     * @dataProvider authProvider
     * Case when permutation requires single item with greater quantity failed due to too small deposit.
     */
    public function testProductRequiresSingleItemWithGreaterQuantityFailed($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        $data = $this->createDataPatternOne();

        $data->get('item')->options()->saveMany([$data->get('optionOne'), $data->get('optionTwo')]);

        $this->product->schemas()->saveMany([$data->get('schemaOne'), $data->get('schemaTwo')]);

        $this->actingAs($this->$user)->postJson('/items/id:' . $data->get('item')->getKey() . '/deposits', [
            'quantity' => 1,
        ]);

        $this->assertTrue(!$this->product->refresh()->available);
        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => false,
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
    }

    /**
     * @dataProvider authProvider
     */
    public function testUnavailableAfterOrder($user): void
    {
        $this->$user->givePermissionTo('orders.add');

        $data = $this->createDataPatternOne();

        $data->get('item')->update([
            'quantity' => 2,
        ]);

        $data->get('item')->options()->saveMany([$data->get('optionOne'), $data->get('optionTwo')]);

        $this->product->schemas()->saveMany([$data->get('schemaOne'), $data->get('schemaTwo')]);

        $this->product->update([
            'available' => true,
        ]);

        $this->actingAs($this->$user)->postJson('/orders', [
            'email' => 'test@test.test',
            'shipping_method_id' => ShippingMethod::factory()->create()->getKey(),
            'delivery_address' => Address::factory()->create()->toArray(),
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
    }

    /**
     * @dataProvider authProvider
     */
    public function testAvailableAfterOrderCancel($user): void
    {
        $data = $this->createDataPatternOne();

        $data->get('item')->update([
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
            'shipping_method_id' => ShippingMethod::factory()->create()->getKey(),
            'delivery_address' => Address::factory()->create()->toArray(),
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

        $this->actingAs($this->$user)->postJson("/orders/id:{$order->getKey()}/status", [
            'status_id' => $statusCancel->getKey(),
        ]);

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

        $item = Item::factory()->create([
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
                    'quantity' => 2,
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
                'quantity' => 2,
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

        $item = Item::factory()->create([
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
                    'quantity' => 20,
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
                'quantity' => 20,
            ]);

        Event::assertDispatched(ProductUpdated::class);
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

        $item = Item::factory()->create([
            'quantity' => 0,
        ]);

        return collect([
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

        $item = Item::factory()->create([
            'quantity' => $itemQuantity,
        ]);

        $this->product->items()->attach($item->getKey(), ['quantity' => 10]);

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
}
