<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Models\Discount;
use Carbon\Carbon;
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
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('discounts.show');

        $response = $this->actingAs($this->$user)->getJson('/discounts');
        $response
            ->assertOk()
            ->assertJsonCount(10, 'data');
    }

    public function testShowUnauthorized(): void
    {
        $discount = Discount::factory()->create();

        $response = $this->getJson('/discounts/' . $discount->code);
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow($user): void
    {
        $this->$user->givePermissionTo('discounts.show_details');
        $discount = Discount::factory()->create();

        $response = $this->actingAs($this->$user)->getJson('/discounts/' . $discount->code);
        $response
            ->assertOk()
            ->assertJsonFragment(['id' => $discount->getKey()]);
    }

    public function testCreateUnauthorized(): void
    {
        $response = $this->postJson('/discounts');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('discounts.add');

        $response = $this->actingAs($this->$user)->json('POST', '/discounts', [
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'type' => DiscountType::PERCENTAGE,
            'max_uses' => 20,
            'starts_at' => Carbon::yesterday()->format('Y-m-d\TH:i'),
            'expires_at' => Carbon::tomorrow()->format('Y-m-d\TH:i')
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
                'starts_at' => Carbon::yesterday(),
                'expires_at' => Carbon::tomorrow()
            ]);

        $this->assertDatabaseHas('discounts', [
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'max_uses' => 20,
            'type' => DiscountType::PERCENTAGE,
            'starts_at' => Carbon::yesterday(),
            'expires_at' => Carbon::tomorrow()
        ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $discount = Discount::factory()->create();

        $response = $this->patchJson('/discounts/id:' .  $discount->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->$user->givePermissionTo('discounts.edit');
        $discount = Discount::factory()->create();

        $response = $this->actingAs($this->$user)
            ->json('PATCH', '/discounts/id:' . $discount->getKey(), [
                'description' => 'Weekend Sale',
                'code' => 'WEEKEND',
                'discount' => 20,
                'type' => DiscountType::AMOUNT,
                'max_uses' => 40,
                'starts_at' => Carbon::yesterday()->format('Y-m-d\TH:i'),
                'expires_at' => Carbon::tomorrow()->format('Y-m-d\TH:i')
            ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'id' => $discount->getKey(),
                'description' => 'Weekend Sale',
                'code' => 'WEEKEND',
                'discount' => 20,
                'type' => DiscountType::AMOUNT,
                'starts_at' => Carbon::yesterday(),
                'expires_at' => Carbon::tomorrow()
            ]);

        $this->assertDatabaseHas('discounts', [
            'id' => $discount->getKey(),
            'description' => 'Weekend Sale',
            'code' => 'WEEKEND',
            'discount' => 20,
            'type' => DiscountType::AMOUNT,
            'max_uses' => 40,
            'starts_at' => Carbon::yesterday(),
            'expires_at' => Carbon::tomorrow()
        ]);
    }

    public function testDeleteUnauthorized(): void
    {
        $discount = Discount::factory()->create();

        $response = $this->deleteJson('/discounts/id:' . $discount->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('discounts.remove');
        $discount = Discount::factory()->create();

        $response = $this->actingAs($this->$user)->deleteJson('/discounts/id:' . $discount->getKey());
        $response->assertNoContent();
        $this->assertSoftDeleted($discount);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateCheckDatetime($user): void
    {
        $this->$user->givePermissionTo('discounts.add');

        $response = $this->actingAs($this->$user)->json('POST', '/discounts', [
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'type' => DiscountType::PERCENTAGE,
            'max_uses' => 20,
            'starts_at' => '2021-09-20T12:00',
            'expires_at' => '2021-09-21T12:00',
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
                'starts_at' => '2021-09-20T12:00:00.000000Z',
                'expires_at' => '2021-09-21T12:00:00.000000Z',
            ]);

        $this->assertDatabaseHas('discounts', [
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'discount' => 10,
            'max_uses' => 20,
            'type' => DiscountType::PERCENTAGE,
            'starts_at' => '2021-09-20T12:00',
            'expires_at' => '2021-09-21T12:00',
        ]);
    }
}
