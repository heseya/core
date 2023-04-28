<?php

namespace Tests\Feature;

use App\Enums\SchemaType;
use App\Models\Item;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Item $item;
    private Product $product;

    public function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create([
            'public' => true,
        ]);

        $this->item = Item::factory()->create();

        $this->product->items()->sync([
            $this->item->getKey() => [
                'required_quantity' => 1,
            ],
        ]);

        $this->item->deposits()->create([
            'quantity' => 10,
            'shipping_time' => 2,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddDeposit($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        $this
            ->actingAs($this->$user)
            ->json('POST', "/items/id:{$this->item->getKey()}/deposits", [
                'quantity' => 16,
                'shipping_time' => 1,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => true,
            'quantity' => 26, // 10 from previous deposit + 16 from new
            'shipping_time' => 1,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testRemovedItemFromDeposit($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        $this
            ->actingAs($this->$user)
            ->json('POST', "/items/id:{$this->item->getKey()}/deposits", [
                'quantity' => -6,
                'shipping_time' => 2,
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => true,
            'quantity' => 4, // 10 from previous deposit -6 from new
            'shipping_time' => 2,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testRemovedAllItemsFromDeposit($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        $this
            ->actingAs($this->$user)
            ->json('POST', "/items/id:{$this->item->getKey()}/deposits", [
                'quantity' => -10,
                'shipping_time' => 2,
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => false,
            'quantity' => 0, // 10 from previous deposit -10 from new
            'shipping_time' => null,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testMultipleItemsDeposits($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        $item2 = Item::factory()->create();
        $item2->deposits()->create([
            'quantity' => 6,
            'shipping_time' => 2,
        ]);
        $item2->deposits()->create([
            'quantity' => 8,
            'shipping_time' => 3,
        ]);

        $item3 = Item::factory()->create();
        $item3->deposits()->create([
            'quantity' => 4,
            'shipping_time' => 2,
        ]);

        $this->product->items()->sync(
            [
                $this->item->getKey() => [
                    'required_quantity' => 1, // 10
                ],
                $item2->getKey() => [
                    'required_quantity' => 1, // 6 + 8 = 14
                ],
                $item3->getKey() => [
                    'required_quantity' => 1, // 4
                ],
            ]
        );

        $this
            ->actingAs($this->$user)
            ->json('POST', "/items/id:{$this->item->getKey()}/deposits", [
                'quantity' => 2,
                'shipping_time' => 2,
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => true,
            'quantity' => 4,
            'shipping_time' => 2,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testMultipleItemsSameQuantityDeposits($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $item1 = Item::factory()->create();
        $item1->deposits()->create([
            'quantity' => 5,
            'shipping_time' => 2,
        ]);

        $item1->deposits()->create([
            'quantity' => 1,
            'shipping_time' => 3,
        ]);

        $item2 = Item::factory()->create();

        $item2->deposits()->create([
            'quantity' => 1,
            'shipping_time' => 2,
        ]);

        $item2->deposits()->create([
            'quantity' => 5,
            'shipping_time' => 3,
        ]);

        $item3 = Item::factory()->create();

        $item3->deposits()->create([
            'quantity' => 5,
            'shipping_time' => 3,
        ]);

        $item4 = Item::factory()->create();

        $item4->deposits()->create([
            'quantity' => 2,
            'shipping_time' => 2,
        ]);

        $product->items()->sync(
            [
                $item1->getKey() => [
                    'required_quantity' => 1,
                ],
                $item2->getKey() => [
                    'required_quantity' => 1,
                ],
                $item3->getKey() => [
                    'required_quantity' => 1,
                ],
                $item4->getKey() => [
                    'required_quantity' => 1,
                ],
            ]
        );

        // After adding this deposit should be:
        //
        // Shipping time 2:
        // item | quantity | overstock |
        //------------------------------
        //   1  |    5     |     0     |
        //   2  |    1     |     0     |
        //   3  |    0     |     0     |
        //   4  |    2     |     0     |
        // 0 product can be sold in shipping_time = 3 (no item 3 in deposit)
        //
        // Shipping time 3:
        // item | quantity | overstock |
        //------------------------------
        //   1  |    1     |     5     |
        //   2  |    5     |     1     |
        //   3  |    5     |     0     |
        //   4  |    7     |     2     |
        // Only 5 products can be sold in shipping_time = 3
        $this
            ->actingAs($this->$user)
            ->json('POST', "/items/id:{$item4->getKey()}/deposits", [
                'quantity' => 7,
                'shipping_time' => 3,
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'available' => true,
            'quantity' => 5,
            'shipping_time' => 3,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testMultipleItemsDifferentRequiredQuantity($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        $item = Item::factory()->create();

        $item->deposits()->create([
            'quantity' => 10,
            'shipping_time' => 2,
        ]);

        $this->product->items()->sync([
            $this->item->getKey() => [
                'required_quantity' => 1,
            ],
            $item->getKey() => [
                'required_quantity' => 3,
            ],
        ]);

        $this
            ->actingAs($this->$user)
            ->json('POST', "/items/id:{$item->getKey()}/deposits", [
                'quantity' => 2,
                'shipping_time' => 3,
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => true,
            'quantity' => 4,
            'shipping_time' => 2,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testItemRequiredBySchema($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        $schema = Schema::factory()->create([
            'required' => true,
            'type' => SchemaType::SELECT,
        ]);
        $option = Option::factory()->create([
            'schema_id' => $schema->getKey(),
        ]);

        $item = Item::factory()->create();
        $item->options()->attach($option->getKey());

        $product = Product::factory()->create();
        $product->schemas()->attach($schema->getKey());

        $this
            ->actingAs($this->$user)
            ->json('POST', "/items/id:{$item->getKey()}/deposits", [
                'quantity' => 2,
                'shipping_time' => 2,
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'available' => true,
            'quantity' => 2,
            'shipping_time' => 2,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testItemRequiredBySchemaAndProduct($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        $schema = Schema::factory()->create([
            'required' => true,
            'type' => SchemaType::SELECT,
            'available' => false,
        ]);

        $this->product->items()->sync([
            $this->item->getKey() => [
                'required_quantity' => 3,
            ],
        ]);

        $this->product->schemas()->attach($schema->getKey());

        $option = Option::factory()->create([
            'schema_id' => $schema->getKey(),
        ]);

        $option->items()->attach($this->item->getKey());

        $this
            ->actingAs($this->$user)
            ->json('POST', "/items/id:{$this->item->getKey()}/deposits", [
                'quantity' => 2,
                'shipping_time' => 3,
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => true,
            'quantity' => 3, // in stock is 10 + 2 when required qty is 3 x by item and 1 x by schema
            'shipping_time' => 2,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOneItemPerShippingTime($user): void
    {
        $this->$user->givePermissionTo('deposits.add');

        $item = Item::factory()->create();

        $item->deposits()->create([
            'shipping_time' => 2,
            'quantity' => 1,
        ]);

        $this->product->items()->sync([
            $item->getKey() => [
                'required_quantity' => 1,
            ],
        ]);

        $this
            ->actingAs($this->$user)
            ->json('POST', "/items/id:{$item->getKey()}/deposits", [
                'quantity' => 1,
                'shipping_time' => 3,
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->getKey(),
            'available' => true,
            'quantity' => 2,
            'shipping_time' => 2,
        ]);
    }
}
