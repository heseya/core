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
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        Product::query()->delete();
        $this->product = Product::factory()->create([
            'available' => false,
            'public' => true,
        ]);

        $this->user->givePermissionTo('deposits.add');
    }

    public function testRestockAvailable()
    {
        Event::fake(ProductUpdated::class);

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

        $this->product->update([
            'available' => false,
        ]);

        $this->actingAs($this->user)->postJson('/items/id:' . $item->getKey() . '/deposits', [
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

        Event::assertDispatched(ProductUpdated::class);

        $this->assertTrue($item->options->every(fn ($option) => $option->available));
        $this->assertTrue($item->options->pluck('schema')->every(fn ($schema) => $schema->available));
        $this->assertTrue($this->product->refresh()->available);

        Event::assertDispatched(ProductUpdated::class);
    }

    /**
     * Case when options' permutations require both items' quantity to be greater than 0, restocking only 1 item.
     */
    public function testRestockUnavailable()
    {
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

        $itemOne = Item::factory()->create([
            'quantity' => 0,
        ]);
        $itemOne->options()->saveMany([$optionOne, $optionTwo]);

        $itemTwo = Item::factory()->create([
            'quantity' => 0,
        ]);
        $itemTwo->options()->saveMany([$optionThree, $optionFour]);

        $this->product->schemas()->saveMany([$schemaOne, $schemaTwo]);

        $this->product->update([
            'available' => false,
        ]);

        $this->actingAs($this->user)->postJson('/items/id:' . $itemTwo->getKey() . '/deposits', [
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

        Event::assertNotDispatched(ProductUpdated::class);
    }

    /**
     * Case when permutation requires single item with greater quantity.
     */
    public function testProductRequiresSingleItemWithGreaterQuantity()
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
        $item->options()->saveMany([$optionOne, $optionTwo]);

        $this->product->schemas()->saveMany([$schemaOne, $schemaTwo]);

        $this->actingAs($this->user)->postJson('/items/id:' . $item->getKey() . '/deposits', [
            'quantity' => 2,
        ]);

        $this->assertTrue($this->product->refresh()->available);
        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => true,
        ])
            ->assertDatabaseHas('schemas', [
                'id' => $schemaOne->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('schemas', [
                'id' => $schemaTwo->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('options', [
                'id' => $optionOne->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('options', [
                'id' => $optionTwo->getKey(),
                'available' => true,
            ]);
    }

    /**
     * Case when permutation requires single item with greater quantity failed due to too small deposit.
     */
    public function testProductRequiresSingleItemWithGreaterQuantityFailed()
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
        $item->options()->saveMany([$optionOne, $optionTwo]);

        $this->product->schemas()->saveMany([$schemaOne, $schemaTwo]);

        $this->actingAs($this->user)->postJson('/items/id:' . $item->getKey() . '/deposits', [
            'quantity' => 1,
        ]);

        $this->assertTrue(!$this->product->refresh()->available);
        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => false,
        ])
            ->assertDatabaseHas('schemas', [
                'id' => $schemaOne->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('schemas', [
                'id' => $schemaTwo->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('options', [
                'id' => $optionOne->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('options', [
                'id' => $optionTwo->getKey(),
                'available' => true,
            ]);
    }

    public function testUnavailableAfterOrder()
    {
        $this->user->givePermissionTo('orders.add');

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
            'quantity' => 2,
        ]);
        $item->options()->saveMany([$optionOne, $optionTwo]);

        $this->product->schemas()->saveMany([$schemaOne, $schemaTwo]);

        $this->product->update([
            'available' => true,
        ]);

        $this->actingAs($this->user)->postJson('/orders', [
            'email' => 'test@test.test',
            'shipping_method_id' => ShippingMethod::factory()->create()->getKey(),
            'delivery_address' => Address::factory()->create()->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $schemaOne->getKey() => $optionOne->getKey(),
                        $schemaTwo->getKey() => $optionTwo->getKey(),
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
                'id' => $schemaOne->getKey(),
                'available' => false,
            ])
            ->assertDatabaseHas('schemas', [
                'id' => $schemaTwo->getKey(),
                'available' => false,
            ])
            ->assertDatabaseHas('options', [
                'id' => $optionOne->getKey(),
                'available' => false,
            ])
            ->assertDatabaseHas('options', [
                'id' => $optionTwo->getKey(),
                'available' => false,
            ]);
    }

    public function testAvailableAfterOrderCancel()
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
            'quantity' => 2,
        ]);
        $item->options()->saveMany([$optionOne, $optionTwo]);

        $this->product->schemas()->saveMany([$schemaOne, $schemaTwo]);

        $this->product->update([
            'available' => true,
            'price' => 0,
        ]);

        $this->user->givePermissionTo('orders.add');
        $this->user->givePermissionTo('orders.edit.status');

        $status = Status::factory()->create([
            'cancel' => false,
        ]);

        $response = $this->actingAs($this->user)->postJson('/orders', [
            'email' => 'test@test.test',
            'shipping_method_id' => ShippingMethod::factory()->create()->getKey(),
            'delivery_address' => Address::factory()->create()->toArray(),
            'items' => [
                [
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [
                        $schemaOne->getKey() => $optionOne->getKey(),
                        $schemaTwo->getKey() => $optionTwo->getKey(),
                    ],
                ],
            ],
        ]);

        $order = Order::find($response->getData()->data->id);

        $statusCancel = Status::factory()->create([
            'cancel' => true,
        ]);

        $this->actingAs($this->user)->postJson("/orders/id:{$order->getKey()}/status", [
            'status_id' => $statusCancel->getKey(),
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => true,
        ])
            ->assertDatabaseHas('schemas', [
                'id' => $schemaOne->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('schemas', [
                'id' => $schemaTwo->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('options', [
                'id' => $optionOne->getKey(),
                'available' => true,
            ])
            ->assertDatabaseHas('options', [
                'id' => $optionTwo->getKey(),
                'available' => true,
            ]);
    }
}

