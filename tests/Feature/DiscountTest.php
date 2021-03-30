<?php

namespace Tests\Feature;

use App\Models\Discount;
use Tests\TestCase;

class DiscountTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Discount::factory()->count(10)->create();
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/discounts');
        $response->assertUnauthorized();
    }

    public function testIndex(): void
    {
        $response = $this->actingAs($this->user)->getJson('/discounts');
        $response
            ->assertOk()
            ->assertJsonCount(10, 'data');
    }
}
