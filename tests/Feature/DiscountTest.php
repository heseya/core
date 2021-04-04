<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
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

    public function testCreate(): void
    {
        $response = $this->actingAs($this->user)->postJson('/discounts', [
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'type' => DiscountType::PERCENTAGE,
        ]);

        dd($response->json());

        $response
            ->assertOk()
            ->assertJsonFragment([
                'description' => 'Testowy kupon',
                'code' => 'S43SA2',
                'discount' => 10,
                'type' => DiscountType::PERCENTAGE,
            ]);
    }
}
