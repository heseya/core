<?php

namespace Unit;

use App\Enums\ConditionType;
use App\Enums\DiscountTargetType;
use App\Repositories\DiscountRepository;
use Domain\Price\Enums\ProductPriceType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Services\DiscountService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Heseya\Dto\DtoException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ActiveSalesTest extends TestCase
{
    use RefreshDatabase;

    private DiscountService $discountService;

    public function setUp(): void
    {
        parent::setUp();

        $this->discountService = App::make(DiscountService::class);
    }

    public static function conditionTypeProvider(): array
    {
        return [
            ConditionType::DATE_BETWEEN->value => [
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
            ConditionType::TIME_BETWEEN->value => [
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
            'name' => 'Active sale - no condition groups',
        ]);

        $sale7 = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'name' => 'Inactive sale - is in range but inactive',
            'active' => false,
        ]);

        $conditionGroup7 = ConditionGroup::create();

        $conditionGroup7->conditions()->create([
            'type' => $conditionType,
            'value' => ['is_in_range' => true] + $inRangeValues,
        ]);

        $sale7->conditionGroups()->attach($conditionGroup7);

        $activeSales = $this->discountService->activeSales();

        $this->assertCount(3, $activeSales);

        $this->assertTrue($activeSales->contains($sale1));
        $this->assertTrue($activeSales->contains($sale5));
        $this->assertTrue($activeSales->contains($sale6));

        $this->assertFalse($activeSales->contains($sale2));
        $this->assertFalse($activeSales->contains($sale3));
        $this->assertFalse($activeSales->contains($sale4));
        $this->assertFalse($activeSales->contains($sale7));
    }

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function testCheckActiveSalesJob(): void
    {
        Carbon::setTestNow('2022-04-21T10:00:00');

        $currency = Currency::DEFAULT->value;

        $discountRepository = App::make(DiscountRepository::class);

        /** @var ProductRepositoryContract $productRepository */
        $productRepository = App::make(ProductRepositoryContract::class);

        $product1 = Product::factory()->create([
            'name' => 'Product had discount',
            'public' => true,
        ]);
        $productRepository->setProductPrices($product1->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(1000, $currency))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(1000, $currency))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(3500, $currency))],
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Product will have discount',
            'public' => true,
        ]);
        $productRepository->setProductPrices($product2->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(2500, $currency))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(2000, $currency))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(4000, $currency))],
        ]);

        $product3 = Product::factory()->create([
            'name' => 'Just the product',
            'public' => true,
        ]);
        $productRepository->setProductPrices($product3->getKey(), [
            ProductPriceType::PRICE_BASE->value => [PriceDto::from(Money::of(1500, $currency))],
            ProductPriceType::PRICE_MIN_INITIAL->value => [PriceDto::from(Money::of(1200, $currency))],
            ProductPriceType::PRICE_MAX_INITIAL->value => [PriceDto::from(Money::of(2000, $currency))],
        ]);

        $sale1 = Discount::factory()->create([
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'name' => 'Old active sale',
            'target_is_allow_list' => true,
            'percentage' => null,
        ]);
        $discountRepository->setDiscountAmounts($sale1->getKey(), [
            PriceDto::from([
                'value' => '200.00',
                'currency' => Currency::DEFAULT,
            ])
        ]);

        $sale1->products()->sync($product1->getKey());

        /** @var ConditionGroup $conditionGroup1 */
        $conditionGroup1 = ConditionGroup::query()->create();

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
            'target_is_allow_list' => true,
            'percentage' => null,
        ]);

        $discountRepository->setDiscountAmounts($sale2->getKey(), [
            PriceDto::from([
                'value' => '300.00',
                'currency' => Currency::DEFAULT,
            ])
        ]);

        $sale2->products()->sync($product2->getKey());

        /** @var ConditionGroup $conditionGroup2 */
        $conditionGroup2 = ConditionGroup::query()->create();

        $conditionGroup2->conditions()->create([
            'type' => ConditionType::TIME_BETWEEN,
            'value' => [
                'start_at' => '11:00:00',
                'end_at' => '15:00:00',
                'is_in_range' => true,
            ],
        ]);

        $sale2->conditionGroups()->attach($conditionGroup2);

        $this->discountService->applyDiscountsOnProducts(Collection::make([$product1, $product2, $product3]), Currency::from($currency));
        Cache::put('sales.active', collect([$sale1->getKey()]));

        $product1->refresh();
        $product2->refresh();
        $product3->refresh();

        $this->assertEquals(800, $product1->pricesMin()->where('currency', $currency)->first()->value->getAmount()->toInt());
        $this->assertEquals(3300, $product1->pricesMax()->where('currency', $currency)->first()->value->getAmount()->toInt());

        $this->assertEquals(2000, $product2->pricesMin()->where('currency', $currency)->first()->value->getAmount()->toInt());
        $this->assertEquals(4000, $product2->pricesMax()->where('currency', $currency)->first()->value->getAmount()->toInt());

        $this->assertEquals(1200, $product3->pricesMin()->where('currency', $currency)->first()->value->getAmount()->toInt());
        $this->assertEquals(2000, $product3->pricesMax()->where('currency', $currency)->first()->value->getAmount()->toInt());

        Carbon::setTestNow('2022-04-21T12:00:00');
        $this->travelTo('2022-04-21T12:00:00');
        $this->artisan('schedule:run');

        $activeSales = Cache::get('sales.active');
        $this->assertCount(1, $activeSales);
        $this->assertTrue($activeSales->contains($sale2->getKey()));
        $this->assertFalse($activeSales->contains($sale1->getKey()));

        $product1->refresh();
        $product2->refresh();
        $product3->refresh();

        $this->assertEquals(1000, $product1->pricesMin()->where('currency', $currency)->first()->value->getAmount()->toInt());
        $this->assertEquals(3500, $product1->pricesMax()->where('currency', $currency)->first()->value->getAmount()->toInt());

        $this->assertEquals(1700, $product2->pricesMin()->where('currency', $currency)->first()->value->getAmount()->toInt());
        $this->assertEquals(3700, $product2->pricesMax()->where('currency', $currency)->first()->value->getAmount()->toInt());

        $this->assertEquals(1200, $product3->pricesMin()->where('currency', $currency)->first()->value->getAmount()->toInt());
        $this->assertEquals(2000, $product3->pricesMax()->where('currency', $currency)->first()->value->getAmount()->toInt());
    }
}
