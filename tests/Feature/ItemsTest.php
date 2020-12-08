<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Item;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ItemsTest extends TestCase
{
    private Item $item;

    private array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->item = Item::factory()->create();

        Deposit::factory()->create([
            'item_id' => $this->item->id,
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

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->getJson('/items');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->getJson('/items');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @return void
     */
    public function testView()
    {
        $response = $this->getJson('/items/id:' . $this->item->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->getJson('/items/id:' . $this->item->getKey());
        $response
            ->assertOk()
            ->assertJson(['data' => $this->expected]);
    }

    /**
     * @return void
     */
    public function testCreate()
    {
        $response = $this->postJson('/items');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
        ];

        $response = $this->postJson('/items', $item);
        $response
            ->assertCreated()
            ->assertJson(['data' => $item]);

        $item = [
            'name' => 'Test NULL sku',
            'sku' => NULL,
        ];

        $response = $this->postJson('/items', $item);
        $response
            ->assertCreated()
            ->assertJson(['data' => $item]);

        $item = [
            'name' => 'Test no sku',
        ];

        $response = $this->postJson('/items', $item);
        $response
            ->assertCreated()
            ->assertJson(['data' => $item + ['sku' => NULL]]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $response = $this->patchJson('/items/id:' . $this->item->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $item = [
            'name' => 'Test 2',
            'sku' => 'TES/T2',
        ];

        $response = $this->patchJson(
            '/items/id:' . $this->item->getKey(),
            $item,
        );
        $response
            ->assertOk()
            ->assertJson(['data' => $item]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->deleteJson('/items/id:' . $this->item->getKey());
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->deleteJson('/items/id:' . $this->item->getKey());
        $response->assertNoContent();
    }
}
