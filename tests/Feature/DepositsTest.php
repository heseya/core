<?php

namespace Tests\Feature;

use App\Item;
use App\Deposit;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DepositsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->item = factory(Item::class)->create();

        $deposit = factory(Deposit::class)->create([
            'item_id' => $this->item->id,
        ]);

        $this->expected = [
            'id' => $deposit->id,
            'quantity' => $deposit->quantity,
            'item_id' => $deposit->item_id,
        ];
    }

    /**
     * @return void
     */
    public function testIndex()
    {
        $response = $this->get('/deposits');

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
        $response = $this->get('/items/id:' . $this->item->id . '/deposits');

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
    public function testCreate()
    {
        $deposit = [
            'quantity' => '12.5',
        ];

        $response = $this->post(
            '/items/id:' . $this->item->id . '/deposits', 
            $deposit,
        );

        $response
            ->assertStatus(201)
            ->assertJson(['data' => $deposit + [
                'item_id' => $this->item->id
            ]]);
    }
}
