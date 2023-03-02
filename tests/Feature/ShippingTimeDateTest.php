<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Item;
use App\Models\Product;
use App\Services\Contracts\AvailabilityServiceContract;
use App\Services\Contracts\ShippingTimeDateServiceContract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class ShippingTimeDateTest extends TestCase
{
    private AvailabilityServiceContract $availabilityService;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityService = App::make(AvailabilityServiceContract::class);
    }

    /**
     * @dataProvider authProvider
     */
    public function testProductWithShippingTimeAndDateAndCountQuantityNeed($user): void
    {
        $itemData = ['unlimited_stock_shipping_date' => Carbon::now()->addDays(10)->toDateTimeString()];

        $item = Item::factory()->create($itemData);
        $item2 = Item::factory()->create();

        /** @var Product $product */
        $product = Product::factory()->create();
        $product->items()->attach($item->getKey(), ['required_quantity' => 1]);

        $deposit1 = Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_date' => Carbon::now()->startOfDay()->addDays(4)->toDateTimeString(),
        ]);
        $product->items()->detach($item->getKey());
        $product->refresh();

        $this->assertNull($product->shipping_date);

        $product->items()->attach($item->getKey(), ['required_quantity' => 1]);
        $product->refresh();

        $this->assertEquals($deposit1->shipping_date, $product->shipping_date);

        $deposit2 = Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_date' => Carbon::now()->startOfDay()->addDays(2)->toDateTimeString(),
        ]);
        $product->items()->detach($item->getKey());
        $product->items()->attach($item->getKey(), ['required_quantity' => 3]);
        $product->refresh();

        $this->assertEquals($deposit1->shipping_date, $product->shipping_date);

        $product->items()->detach($item->getKey());
        $product->items()->attach($item->getKey(), ['required_quantity' => 2]);
        $product->refresh();

        $this->assertEquals($deposit2->shipping_date, $product->shipping_date);

        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_date' => Carbon::now()->startOfDay()->addDays(6)->toDateTimeString(),
        ]);
        $this->availabilityService->calculateProductAvailability($product);
        $product->refresh();

        $item->update(['unlimited_stock_shipping_time' => 10]);
        $product->refresh();
        $this->availabilityService->calculateProductAvailability($product);
        $product->refresh();

        $this->assertEquals(10, $product->shipping_time);

        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 3.0,
            'shipping_time' => 6,
        ]);
        $this->availabilityService->calculateProductAvailability($product);
        $product->refresh();

        $this->assertEquals(6, $product->shipping_time);

        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 3.0,
            'shipping_time' => 4,
        ]);
        $this->availabilityService->calculateProductAvailability($product);
        $product->refresh();

        $this->assertEquals(4, $product->shipping_time);

        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 3.0,
            'shipping_time' => 8,
        ]);
        $this->availabilityService->calculateProductAvailability($product);
        $product->refresh();

        $this->assertEquals(4, $product->shipping_time);

        $product->items()->attach($item2->getKey(), ['required_quantity' => 5.0]);

        Deposit::factory()->create([
            'item_id' => $item2->getKey(),
            'quantity' => 4.0,
            'shipping_date' => Carbon::now()->addDays(4)->toDateTimeString(),
        ]);
        $this->availabilityService->calculateProductAvailability($product);
        $product->refresh();

        $this->assertNull($product->shipping_date);

        $depositItem2 = Deposit::factory()->create([
            'item_id' => $item2->getKey(),
            'quantity' => 4.0,
            'shipping_time' => 2,
        ]);

        $this->availabilityService->calculateItemAvailability($item2);
        $product->refresh();

        $depositItem2->update(['quantity' => 10]);
        $product->refresh();
        $this->availabilityService->calculateProductAvailability($product);
        $product->refresh();

        $this->assertEquals(4, $product->shipping_time);

        $product->items()->detach($item->getKey());

        $this->$user->givePermissionTo('deposits.add');

        $deposit = [
            'quantity' => 120,
            'shipping_time' => 1,
        ];

        $response = $this->actingAs($this->$user)->postJson(
            "/items/id:{$item2->getKey()}/deposits",
            $deposit,
        );

        $response->assertCreated();
        $this->assertDatabaseHas('deposits', $deposit);
        $product->refresh();

        $this->assertEquals(1, $product->shipping_time);
    }

    public function testShippingDateAfterShippingTime(): void
    {
        /** @var Product $product */
        $product = Product::factory()->create();
        $item = Item::factory()->create();

        $product->items()->attach($item->getKey(), ['required_quantity' => 5]);

        $depositItem1 = Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 4.0,
            'shipping_date' => Carbon::now()->startOfDay()->addDays(4)->toDateTimeString(),
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'available' => false,
            'shipping_time' => null,
            'shipping_date' => null,
        ]);

        $depositItem2 = Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 4.0,
            'shipping_time' => 2,
        ]);

        $this->availabilityService->calculateItemAvailability($item);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'available' => true,
            'shipping_time' => null,
            'shipping_date' => $depositItem1->shipping_date,
        ]);

        $depositItem2->update(['quantity' => 10]);
        $this->availabilityService->calculateItemAvailability($item);

        $this->assertDatabaseHas('products', [
            'id' => $product->getKey(),
            'available' => true,
            'shipping_time' => $depositItem2->shipping_time,
            'shipping_date' => null,
        ]);
    }

    public function testStopUnlimitedStockShippingDate(): void
    {
        $shippingTimeDateService = App::make(ShippingTimeDateServiceContract::class);

        $itemData = ['unlimited_stock_shipping_date' => Carbon::now()->addHours(-12)->toDateTimeString()];

        $item = Item::factory()->create($itemData);

        /** @var Product $product */
        $product = Product::factory()->create();
        $product->items()->attach($item->getKey(), ['required_quantity' => 1]);

        $this->assertNull($product->shipping_date);
        $now = Carbon::today()->addDays(2);
        $product->update(['shipping_date' => $now->toIso8601String()]);
        $product->refresh();

        $this->assertEquals($now, $product->shipping_date);

        $shippingTimeDateService->stopShippingUnlimitedStockDate();
        $product->refresh();

        $this->assertNull($product->shipping_date);
    }
}
