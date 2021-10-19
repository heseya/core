<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Item;
use Tests\TestCase;

class ItemTest extends TestCase
{
    private Item $item;

    private array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->item = Item::factory()->create();

        Deposit::factory()->create([
            'item_id' => $this->item->getKey(),
        ]);

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->item->getKey(),
            'name' => $this->item->name,
            'sku' => $this->item->sku,
            'quantity' => $this->item->quantity,
        ];
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/items');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('items.show');

        $this
            ->actingAs($this->user)
            ->getJson('/items')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);

        $this->assertQueryCountLessThan(10);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexPerformance($user): void
    {
        $this->$user->givePermissionTo('items.show');

        Item::factory()->count(499)->create();

        $this
            ->actingAs($this->$user)
            ->getJson('/items?limit=500')
            ->assertOk()
            ->assertJsonCount(500, 'data');

        $this->assertQueryCountLessThan(10);
    }

    public function testViewUnauthorized(): void
    {
        $response = $this->getJson('/items/id:' . $this->item->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testView($user): void
    {
        $this->$user->givePermissionTo('items.show_details');

        $this
            ->actingAs($this->user)
            ->getJson('/items/id:' . $this->item->getKey())
            ->assertOk()
            ->assertJson(['data' => $this->expected]);

        $this->assertQueryCountLessThan(10);
    }

    public function testCreateUnauthorized(): void
    {
        $response = $this->postJson('/items');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('items.add');

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
        ];

        $response = $this->actingAs($this->$user)->postJson('/items', $item);
        $response
            ->assertCreated()
            ->assertJson(['data' => $item]);

        $this->assertDatabaseHas('items', $item);
    }

    public function testUpdateUnauthorized(): void
    {
        $response = $this->patchJson('/items/id:' . $this->item->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->$user->givePermissionTo('items.edit');

        $item = [
            'name' => 'Test 2',
            'sku' => 'TES/T2',
        ];

        $response = $this->actingAs($this->$user)->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $item]);

        $this->assertDatabaseHas('items', $item + ['id' => $this->item->getKey()]);
    }

    public function testDeleteUnauthorized(): void
    {
        $this
            ->json('DELETE', '/items/id:' . $this->item->getKey())
            ->assertForbidden();

        $this->assertDatabaseHas('items', [
            'id' => $this->item->getKey(),
            'sku' => $this->item->sku,
            'name' => $this->item->name,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('items.remove');

        $this
            ->actingAs($this->user)
            ->deleteJson('/items/id:' . $this->item->getKey())
            ->assertNoContent();

        $this->assertSoftDeleted($this->item);
    }
}
