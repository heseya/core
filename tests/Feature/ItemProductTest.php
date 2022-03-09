<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ItemProductTest extends TestCase
{
    private Product $product;
    private Collection $items;

    public function setUp(): void
    {
        parent::setUp();
        Product::query()->delete();
        Item::query()->delete();
        $this->product = Product::factory()->create();
        $this->items = Item::factory()->count(3)->create();
    }

    /**
     * @dataProvider authProvider
     */
    public function testStoreProductWithItems($user)
    {
        $this->$user->givePermissionTo('products.add');
        $response = $this->actingAs($this->$user)->postJson('/products', [
            'name' => 'test',
            'slug' => 'test',
            'price' => 50,
            'public' => true,
            'items' => [
                [
                    'id' => $this->items->first()->getKey(),
                    'quantity' => 5,
                ],
                [
                    'id' => $this->items->last()->getKey(),
                    'quantity' => 15,
                ],
            ],
        ]);
        $response
            ->assertCreated()
            ->assertJsonCount(2, 'data.items');
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductWithItems($user)
    {
        $this->$user->givePermissionTo('products.edit');
        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'price' => 50,
            'public' => true,
            'items' => [
                [
                    'id' => $this->items->first()->getKey(),
                    'quantity' => 5,
                ],
                [
                    'id' => $this->items->last()->getKey(),
                    'quantity' => 15,
                ],
            ],
        ]);
        $response
            ->assertOk()
            ->assertJsonCount(2, 'data.items');
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductWithoutItems($user)
    {
        $this->$user->givePermissionTo('products.edit');
        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'price' => 50,
            'public' => true,
        ]);
        $response
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductWithEmptyItems($user)
    {
        $this->$user->givePermissionTo('products.edit');
        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'price' => 50,
            'public' => true,
            'items' => [],
        ]);
        $response
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
        $this->assertDatabaseCount('item_product', 0);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductWithItemsOverride($user)
    {
        $this->product->items()->attach($this->items->get(0)->getKey(), ['quantity' => 5]);
        $this->product->items()->attach($this->items->get(1)->getKey(), ['quantity' => 15]);

        $this->$user->givePermissionTo('products.edit');
        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'price' => 50,
            'public' => true,
            'items' => [
                [
                    'id' => $this->items->get(2)->getKey(),
                    'quantity' => 20,
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.items');

        $this
            ->assertDatabaseCount('item_product', 1)
            ->assertDatabaseHas('item_product', [
                'item_id' => $this->items->get(2)->getKey(),
                'product_id' => $this->product->getKey(),
                'quantity' => 20,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductWithClearItems($user)
    {
        $this->product->items()->attach($this->items->get(0)->getKey(), ['quantity' => 5]);
        $this->product->items()->attach($this->items->get(1)->getKey(), ['quantity' => 15]);

        $this->$user->givePermissionTo('products.edit');
        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'price' => 50,
            'public' => true,
            'items' => [],
        ]);

        $response
            ->assertOk()
            ->assertJsonCount(0, 'data.items');

        $this
            ->assertDatabaseCount('item_product', 0)
            ->assertDatabaseMissing('item_product', [
                'item_id' => $this->items->get(0)->getKey(),
                'product_id' => $this->product->getKey(),
                'quantity' => 5,
            ])
            ->assertDatabaseMissing('item_product', [
                'item_id' => $this->items->get(1)->getKey(),
                'product_id' => $this->product->getKey(),
                'quantity' => 15,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductWithoutItemsOverride($user)
    {
        $this->product->items()->attach($this->items->get(0)->getKey(), ['quantity' => 5]);
        $this->product->items()->attach($this->items->get(1)->getKey(), ['quantity' => 15]);

        $this->$user->givePermissionTo('products.edit');
        $response = $this->actingAs($this->$user)->patchJson('/products/id:' . $this->product->getKey(), [
            'name' => 'test',
            'slug' => 'test',
            'price' => 50,
            'public' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data.items');

        $this
            ->assertDatabaseCount('item_product', 2)
            ->assertDatabaseHas('item_product', [
                'item_id' => $this->items->get(0)->getKey(),
                'product_id' => $this->product->getKey(),
                'quantity' => 5,
            ])
            ->assertDatabaseHas('item_product', [
                'item_id' => $this->items->get(1)->getKey(),
                'product_id' => $this->product->getKey(),
                'quantity' => 15,
            ]);
    }
}
