<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Item;
use Carbon\Carbon;
use Domain\Currency\Currency;
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
        $prices = array_map(fn (Currency $currency) => [
            'value' => '10.00',
            'currency' => $currency->value,
        ], Currency::cases());

        return [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'test',
            'prices_base' => $prices,
            'public' => true,
            'shipping_digital' => false,
            'items' => [
                [
                    'id' => $item->getKey(),
                    'required_quantity' => 1,
                ],
            ],
        ];
    }
}
