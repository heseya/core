<?php

namespace Tests\Unit\Discounts;

use App\Dtos\CartDto;
use App\Enums\ConditionType;
use App\Models\Discount;
use App\Models\DiscountCondition;
use App\Models\Product;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Dto\DtoException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Tests\Utils\FakeDto;

class DiscountConditionsCheckCouponsCountTest extends DiscountConditionsCheckCase
{
    /**
     * @return array<string, array<int, array<string, int>|bool|int>>
     */
    public static function couponsCountProvider(): array
    {
        return [
            'pass min-max min value' => [
                3,
                [
                    'min_value' => 3,
                    'max_value' => 10,
                ],
                true,
            ],
            'pass min-max max value' => [
                3,
                [
                    'min_value' => 1,
                    'max_value' => 3,
                ],
                true,
            ],
            'pass only min value' => [
                3,
                [
                    'min_value' => 2,
                ],
                true,
            ],
            'pass only max value' => [
                2,
                [
                    'max_value' => 5,
                ],
                true,
            ],
            'fail min-max min value' => [
                4,
                [
                    'min_value' => 5,
                    'max_value' => 10,
                ],
                false,
            ],
            'fail min-max max value' => [
                6,
                [
                    'min_value' => 3,
                    'max_value' => 5,
                ],
                false,
            ],
            'fail only min value' => [
                2,
                [
                    'min_value' => 10,
                ],
                false,
            ],
            'fail only max value' => [
                6,
                [
                    'max_value' => 5,
                ],
                false,
            ],
        ];
    }

    /**
     * @return array<string, array<int, bool>>
     */
    public static function couponsCountWithSalesProvider(): array
    {
        return [
            'pass' => [true],
            'fail' => [false],
        ];
    }

    private ProductService $productService;
    private ShippingMethod $shippingMethod;
    private Product $product;

    public function setUp(): void
    {
        parent::setUp();

        $this->productService = App::make(ProductService::class);
        $this->shippingMethod = ShippingMethod::factory()->create();

        $this->product = $this->productService->create(FakeDto::productCreateDto());
    }

    /**
     * @dataProvider couponsCountProvider
     *
     * @param array<string, int> $value
     *
     * @throws DtoException
     */
    public function testCheckConditionCouponsCount(int $quantity, array $value, bool $result): void
    {
        /** @var Collection<int, Discount> $coupons */
        $coupons = Discount::factory()->count($quantity)->create();

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => Arr::pluck($coupons, 'code'),
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::COUPONS_COUNT,
            'value' => $value,
        ]);

        $this->assertTrue(count($cart->getCoupons()) === $quantity);
        $this->assertTrue(
            $this->discountService->checkCondition(
                $discountCondition,
                Money::zero($this->currency->value),
                $cart
            ) === $result
        );
    }

    /**
     * @dataProvider couponsCountWithSalesProvider
     *
     * @throws DtoException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     */
    public function testCheckConditionCouponsCountWithSales(bool $result): void
    {
        Discount::factory()->create(['code' => null]);

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $this->product->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $value = $result ? ['max_value' => 0] : ['min_value' => 1];

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::COUPONS_COUNT,
            'value' => $value,
        ]);

        $this->assertEmpty($cart->getCoupons());
        $this->assertTrue(
            $this->discountService->checkCondition(
                $discountCondition,
                Money::zero($this->currency->value),
                $cart
            ) === $result
        );
    }
}
