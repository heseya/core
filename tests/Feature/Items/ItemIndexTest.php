<?php

namespace Tests\Feature\Items;

use App\Models\Deposit;
use App\Models\Item;
use Illuminate\Support\Carbon;

class ItemIndexTest extends ItemTestCase
{
    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/items');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        $this
            ->actingAs($this->{$user})
            ->getJson('/items')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);

        $this->assertQueryCountLessThan(12);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByIds(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        Item::factory()->count(10)->create();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/items', [
                'ids' => [
                    $this->item->getKey(),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexPerformance(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        Item::factory()->count(499)->create();

        $this
            ->actingAs($this->{$user})
            ->getJson('/items?limit=500')
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(12);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilterByAvailable(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        Deposit::factory([
            'quantity' => 10,
        ])->create([
            'item_id' => $this->item->getKey(),
        ]);

        $this->item->refresh();

        $item_sold_out = Item::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/items', ['sold_out' => 0])
            ->assertOk()
            ->assertJsonMissing(['id' => $item_sold_out->getKey()])
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    0 => [
                        'id' => $this->item->getKey(),
                        'name' => $this->item->name,
                        'sku' => $this->item->sku,
                        'quantity' => $this->item->quantity,
                    ],
                ],
            ]);

        $this->assertQueryCountLessThan(12);
    }

    /**
     * @dataProvider authWithTwoBooleansProvider
     */
    public function testIndexFilterBySoldOut($user, $boolean, $booleanValue): void
    {
        $this->{$user}->givePermissionTo('items.show');

        Deposit::factory([
            'quantity' => 10,
        ])->create([
            'item_id' => $this->item->getKey(),
        ]);

        $item_sold_out = Item::factory()->create();

        $itemId = $booleanValue ? $item_sold_out->getKey() : $this->item->getKey();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/items', ['sold_out' => $boolean])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $itemId,
            ]);

        $this->assertQueryCountLessThan(12);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilterBySoldOutAndDay(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/items', [
                'sold_out' => 1,
                'day' => Carbon::now(),
            ])
            ->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSortByQuantityAndFilterByDay(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/items', [
                'sort' => 'quantity:asc',
                'day' => Carbon::now(),
            ])
            ->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilterByDay(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        $created_at = Carbon::yesterday()->startOfDay()->addHours(12);

        $item2 = Item::factory()->create([
            'created_at' => $created_at,
        ]);
        Deposit::factory([
            'quantity' => 5,
            'created_at' => $created_at,
        ])->create([
            'item_id' => $item2->getKey(),
        ]);

        Deposit::factory([
            'quantity' => 5,
        ])->create([
            'item_id' => $item2->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/items', ['day' => $created_at->format('Y-m-d')])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $item2->getKey(),
                'name' => $item2->name,
                'sku' => $item2->sku,
                'quantity' => 5,
            ]);

        $this->assertQueryCountLessThan(12);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilterByDayWithHour(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');

        $item2 = Item::factory()->create([
            'created_at' => Carbon::yesterday(),
        ]);
        Deposit::factory([
            'quantity' => 5,
            'created_at' => Carbon::yesterday(),
        ])->create([
            'item_id' => $item2->getKey(),
        ]);

        Deposit::factory([
            'quantity' => 5,
        ])->create([
            'item_id' => $item2->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/items', ['day' => Carbon::yesterday()])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $item2->getKey(),
                'name' => $item2->name,
                'sku' => $item2->sku,
                'quantity' => 5,
            ]);

        $this->assertQueryCountLessThan(12);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWhitAvailability(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show');
        $this->{$user}->givePermissionTo('items.show_details');
        $this->{$user}->givePermissionTo('deposits.add');

        $item = Item::factory()->create();

        $deposit = [
            'quantity' => 10,
            'shipping_time' => 10,
        ];

        $this->actingAs($this->{$user})->postJson(
            "/items/id:{$item->getKey()}/deposits",
            $deposit,
        )->assertCreated();

        $this
            ->actingAs($this->{$user})
            ->getJson('/items/id:' . $item->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'availability' => [
                    ['quantity' => 10, 'shipping_time' => 10, 'shipping_date' => null, 'from_unlimited' => false],
                ],
            ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/items')
            ->assertOk()
            ->assertJsonFragment([
                'availability' => [
                    ['quantity' => 10, 'shipping_time' => 10, 'shipping_date' => null, 'from_unlimited' => false],
                ],
            ]);
    }
}
