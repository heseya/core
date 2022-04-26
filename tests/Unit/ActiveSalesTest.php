<?php

namespace Unit;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Product;
use App\Services\Contracts\DiscountServiceContract;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ActiveSalesTest extends TestCase
{
    use RefreshDatabase;

    private DiscountServiceContract $discountService;

    public function setUp(): void
    {
        parent::setUp();

        $this->discountService = App::make(DiscountServiceContract::class);
    }

    public function conditionTypeProvider(): array
    {
        return [
            ConditionType::DATE_BETWEEN => [
                ConditionType::DATE_BETWEEN,
                [
                    'start_at' => '2022-04-20T12:00:00',
                    'end_at' => '2022-04-22T12:00:00',
                ],
                [
                    'start_at' => '2022-05-20',
                    'end_at' => '2022-05-22',
                ],
            ],
            ConditionType::TIME_BETWEEN => [
                ConditionType::TIME_BETWEEN,
                [
                    'start_at' => '10:00:00',
                    'end_at' => '15:00:00',
                ],
                [
                    'start_at' => '15:00:00',
                    'end_at' => '20:00:00',
                ],
            ],
        ];
    }

    /**
     * @dataProvider conditionTypeProvider
     */
    public function testActiveSales($conditionType, $inRangeValues, $notInRangeValues): void
    {
        Carbon::setTestNow('2022-04-21T12:00:00');

        $sale1 = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'name' => 'Active sale - is in range',
        ]);

        $conditionGroup1 = ConditionGroup::create();

        $conditionGroup1->conditions()->create([
            'type' => $conditionType,
            'value' => ['is_in_range' => true] + $inRangeValues,
        ]);

        $sale1->conditionGroups()->attach($conditionGroup1);

        $sale2 = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'name' => 'Inactive sale - is in range false',
        ]);

        $conditionGroup2 = ConditionGroup::create();

        $conditionGroup2->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => ['is_in_range' => false] + $inRangeValues,
        ]);

        $sale2->conditionGroups()->attach($conditionGroup2);

        $sale3 = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'name' => 'Inactive sale - wrong condition',
        ]);

        $conditionGroup3 = ConditionGroup::create();

        $conditionGroup3->conditions()->create([
            'type' => ConditionType::COUPONS_COUNT,
            'value' => [
                'min_value' => 1,
                'max_value' => 2,
            ],
        ]);

        $sale3->conditionGroups()->attach($conditionGroup3);

        $sale4 = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'name' => 'Inactive sale - wrong target type',
        ]);

        $conditionGroup4 = ConditionGroup::create();

        $conditionGroup4->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => ['is_in_range' => true] + $inRangeValues,
        ]);

        $sale4->conditionGroups()->attach($conditionGroup4);

        $sale5 = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'name' => 'Active sale - is not in range',
        ]);

        $conditionGroup5 = ConditionGroup::create();

        $conditionGroup5->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => ['is_in_range' => false] + $notInRangeValues,
        ]);

        $sale5->conditionGroups()->attach($conditionGroup5);

        $sale6 = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'name' => 'Inactive sale - no condition groups',
        ]);

        $activeSales = $this->discountService->activeSales();

        $this->assertCount(2, $activeSales);

        $this->assertTrue($activeSales->contains($sale1));
        $this->assertTrue($activeSales->contains($sale5));

        $this->assertFalse($activeSales->contains($sale2));
        $this->assertFalse($activeSales->contains($sale3));
        $this->assertFalse($activeSales->contains($sale4));
        $this->assertFalse($activeSales->contains($sale6));
    }

    public function testCheckActiveSalesJob(): void
    {
        Carbon::setTestNow('2022-04-21T10:00:00');

        $product1 = Product::factory()->create([
            'name' => 'Product had discount',
            'public' => true,
            'price' => 1000,
            'price_min_initial' => 1000,
            'price_max_initial' => 3500,
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Product will have discount',
            'public' => true,
            'price' => 2500,
            'price_min_initial' => 2000,
            'price_max_initial' => 4000,
        ]);

        $product3 = Product::factory()->create([
            'name' => 'Just the product',
            'public' => true,
            'price' => 1500,
            'price_min_initial' => 1200,
            'price_max_initial' => 2000,
        ]);

        $sale1 = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'name' => 'Old active sale',
            'type' => DiscountType::AMOUNT,
            'value' => 200,
            'target_is_allow_list' => true,
        ]);

        $sale1->products()->sync($product1->getKey());

        $conditionGroup1 = ConditionGroup::create();

        $conditionGroup1->conditions()->create([
            'type' => ConditionType::TIME_BETWEEN,
            'value' => [
                'start_at' => '08:00:00',
                'end_at' => '11:00:00',
                'is_in_range' => true,
            ],
        ]);

        $sale1->conditionGroups()->attach($conditionGroup1);

        $sale2 = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'name' => 'New active sale',
            'type' => DiscountType::AMOUNT,
            'value' => 300,
            'target_is_allow_list' => true,
        ]);

        $sale2->products()->sync($product2->getKey());

        $conditionGroup2 = ConditionGroup::create();

        $conditionGroup2->conditions()->create([
            'type' => ConditionType::TIME_BETWEEN,
            'value' => [
                'start_at' => '11:00:00',
                'end_at' => '15:00:00',
                'is_in_range' => true,
            ],
        ]);

        $sale2->conditionGroups()->attach($conditionGroup2);

        $this->discountService->applyDiscountsOnProducts(Collection::make([$product1, $product2, $product3]));
        Cache::put('sales.active', [$sale1->getKey()]);

        $product1->refresh();
        $product2->refresh();
        $product3->refresh();

        $this->assertEquals(800, $product1->price_min);
        $this->assertEquals(3300, $product1->price_max);

        $this->assertEquals(2000, $product2->price_min);
        $this->assertEquals(4000, $product2->price_max);

        $this->assertEquals(1200, $product3->price_min);
        $this->assertEquals(2000, $product3->price_max);

        Carbon::setTestNow('2022-04-21T12:00:00');
        $this->travelTo('2022-04-21T12:00:00');
        $this->artisan('schedule:run');

        $product1->refresh();
        $product2->refresh();
        $product3->refresh();

        $this->assertEquals(1000, $product1->price_min);
        $this->assertEquals(3500, $product1->price_max);

        $this->assertEquals(1700, $product2->price_min);
        $this->assertEquals(3700, $product2->price_max);

        $this->assertEquals(1200, $product3->price_min);
        $this->assertEquals(2000, $product3->price_max);

        $activeSales = Cache::get('sales.active');
        $this->assertCount(1, $activeSales);
        $this->assertTrue($activeSales->contains($sale2->getKey()));
        $this->assertFalse($activeSales->contains($sale1->getKey()));
    }
}
