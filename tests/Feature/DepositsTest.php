<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Deposit;
use Tests\TestCase;
use Laravel\Passport\Passport;
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
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->get('/deposits');

        $response
            ->assertOk()
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
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->get('/items/id:' . $this->item->id . '/deposits');

        $response
            ->assertOk()
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
        $response = $this->post('/items/id:' . $this->item->id . '/deposits');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $deposit = [
            'quantity' => '12.5',
        ];

        $response = $this->post(
            '/items/id:' . $this->item->id . '/deposits',
            $deposit,
        );

        $response
            ->assertCreated()
            ->assertJson(['data' => $deposit + [
                'item_id' => $this->item->id
            ]]);
    }
}
