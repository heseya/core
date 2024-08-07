<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Item;
use Carbon\Carbon;
use Tests\TestCase;

class ProductShippingCacheTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testCreateProductWithUnlimitedShippingTimeItem($user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $time = 1;
        $item = Item::factory()->create([
            'unlimited_stock_shipping_time' => $time,
        ]);

        $this
            ->actingAs($this->{$user})
            ->postJson('/products', $this->productDataWithItem($item))
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'shipping_time' => $time,
                    'shipping_date' => null,
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductWithUnlimitedShippingDateItem($user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $date = Carbon::now()->startOfDay()->addDays(7)->toIso8601String();
        $item = Item::factory()->create([
            'unlimited_stock_shipping_date' => $date,
        ]);

        $this
            ->actingAs($this->{$user})
            ->postJson('/products', $this->productDataWithItem($item))
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'shipping_time' => null,
                    'shipping_date' => $date,
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductShippingTimeItem($user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $time = 1;
        $item = Item::factory()->create();
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 1,
            'shipping_time' => $time,
        ]);

        $this
            ->actingAs($this->{$user})
            ->postJson('/products', $this->productDataWithItem($item))
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'shipping_time' => $time,
                    'shipping_date' => null,
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductShippingDateItem($user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $date = Carbon::now()->startOfDay()->addDays(7)->toIso8601String();
        $item = Item::factory()->create();
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 1,
            'shipping_date' => $date,
        ]);

        $this
            ->actingAs($this->{$user})
            ->postJson('/products', $this->productDataWithItem($item))
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'shipping_time' => null,
                    'shipping_date' => $date,
                ],
            ]);
    }

    /*
     * TODO: Test async updates to product cache
     * E.g. Assigned item got shipping times edited
     */
    private function productDataWithItem(Item $item): array
    {
        return [
            'name' => 'Test',
            'slug' => 'test',
            'price' => 100.00,
            'description_html' => '<h1>Description</h1>',
            'description_short' => 'So called short description...',
            'public' => true,
            'shipping_digital' => false,
            'vat_rate' => 23,
            'items' => [
                [
                    'id' => $item->getKey(),
                    'required_quantity' => 1,
                ],
            ],
        ];
    }
}
