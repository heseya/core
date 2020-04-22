<?php

namespace Tests\Feature;

use App\Item;
use App\Deposit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ItemsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->item = factory(Item::class)->create();

        $deposit = factory(Deposit::class)->create([
            'item_id' => $this->item->id,
        ]);

        /**
         * Expected response
         */
        $this->expected = [
            'id' => $this->item->id,
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
        $response = $this->get('/items');

        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data') // Only one item xD
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    /**
     * @return void
     */
    public function testView()
    {
        $response = $this->get('/items/id:' . $this->item->id);

        $response
            ->assertStatus(200)
            ->assertJson(['data' => $this->expected]);
    }

    /**
     * @return void
     */
    public function testCreate()
    {
        $item = [
            'name' => 'Test',
            'sku' => 'TES/T1',
        ];

        $response = $this->post('/items', $item);

        $response
            ->assertStatus(201)
            ->assertJson(['data' => $item]);

        $item = [
            'name' => 'Test NULL sku',
            'sku' => NULL,
        ];

        $response = $this->post('/items', $item);

        $response
            ->assertStatus(201)
            ->assertJson(['data' => $item]);

        $item = [
            'name' => 'Test no sku',
        ];

        $response = $this->post('/items', $item);

        $response
            ->assertStatus(201)
            ->assertJson(['data' => $item + ['sku' => NULL]]);
    }

    /**
     * @return void
     */
    public function testUpdate()
    {
        $item = [
            'name' => 'Test 2',
            'sku' => 'TES/T2',
        ];

        $response = $this->patch(
            '/items/id:' . $this->item->id,
            $item,
        );

        $response
            ->assertStatus(200)
            ->assertJson(['data' => $item]);
    }

    /**
     * @return void
     */
    public function testDelete()
    {
        $response = $this->delete('/items/id:' . $this->item->id);

        $response->assertStatus(204);
    }
}
