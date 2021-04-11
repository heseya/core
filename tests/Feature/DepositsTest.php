<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Item;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DepositsTest extends TestCase
{
    private Item $item;
    private array $expected;

    public function setUp(): void
    {
        parent::setUp();

        $this->item = Item::factory()->create();

        $deposit = Deposit::factory()->create([
            'item_id' => $this->item->getKey(),
        ]);

        $this->expected = [
            'id' => $deposit->getKey(),
            'quantity' => $deposit->quantity,
            'item_id' => $deposit->item_id,
        ];
    }

    public function testIndex(): void
    {
        $response = $this->getJson('/deposits');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->getJson('/deposits');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    public function testView(): void
    {
        $response = $this->getJson('/items/id:' . $this->item->getKey() . '/deposits');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $response = $this->getJson('/items/id:' . $this->item->getKey() . '/deposits');
        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [
                0 => $this->expected,
            ]]);
    }

    public function testCreate(): void
    {
        $response = $this->postJson('/items/id:' . $this->item->getKey() . '/deposits');
        $response->assertUnauthorized();

        Passport::actingAs($this->user);

        $deposit = [
            'quantity' => 12.5,
        ];

        $response = $this->postJson(
            '/items/id:' . $this->item->getKey() . '/deposits',
            $deposit,
        );

        $response
            ->assertCreated()
            ->assertJson(['data' => $deposit + [
                'item_id' => $this->item->getKey(),
            ]]);

        $this->assertDatabaseHas('deposits', ['item_id' => $this->item->getKey()] + $deposit);
    }
}
