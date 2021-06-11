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

    public function testShow(): void
    {
        $discount = Discount::factory()->create();

        $response = $this->getJson('/discounts/' . $discount->code);
        $response
            ->assertOk()
            ->assertJsonFragment(['id' => $discount->getKey()]);
    }

    public function testCreateUnauthorized(): void
    {
        $response = $this->postJson('/discounts');
        $response->assertUnauthorized();
    }

    public function testCreate(): void
    {
        $response = $this->actingAs($this->user)->postJson('/discounts', [
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'type' => DiscountType::PERCENTAGE,
            'max_uses' => 20,
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'description' => 'Testowy kupon',
                'code' => 'S43SA2',
                'discount' => 10,
                'type' => DiscountType::PERCENTAGE,
                'max_uses' => 20,
                'uses' => 0,
                'available' => true,
            ]);

        $this->assertDatabaseHas('discounts', [
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'max_uses' => 20,
            'type' => DiscountType::PERCENTAGE,
        ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $discount = Discount::factory()->create();

        $response = $this->patchJson('/discounts/id:' .  $discount->getKey());
        $response->assertUnauthorized();
    }

    public function testUpdate(): void
    {
        $discount = Discount::factory()->create();

        $response = $this->actingAs($this->user)->patchJson('/discounts/id:' . $discount->getKey(), [
            'description' => 'Weekend Sale',
            'code' => 'WEEKEND',
            'discount' => 20,
            'type' => DiscountType::AMOUNT,
            'max_uses' => 40,
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $discount->getKey(),
                'description' => 'Weekend Sale',
                'code' => 'WEEKEND',
                'discount' => 20,
                'type' => DiscountType::AMOUNT,
            ]);

        $this->assertDatabaseHas('discounts', [
            'id' => $discount->getKey(),
            'description' => 'Weekend Sale',
            'code' => 'WEEKEND',
            'discount' => 20,
            'type' => DiscountType::AMOUNT,
            'max_uses' => 40,
        ]);
    }
}