<?php

namespace Tests\Unit\Discounts;

use App\Dtos\CartDto;
use App\Enums\ConditionType;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\DiscountCondition;
use App\Models\Product;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\DiscountConditionPriceType;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Dto\DtoException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class DiscountConditionsCheckTest extends TestCase
{
    use RefreshDatabase;

    private DiscountServiceContract $discountService;
    private ConditionGroup $conditionGroup;
    private Discount $discount;
    private ShippingMethod $shippingMethod;
    private Product $product;
    private ProductSet $set;
    private Currency $currency;
    private ProductService $productService;

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->discount = Discount::factory()->create([
            'active' => true,
        ]);
        $this->conditionGroup = ConditionGroup::create();
        $this->shippingMethod = ShippingMethod::factory()->create();

        $this->currency = Currency::DEFAULT;
        $this->productService = App::make(ProductService::class);

        $this->product = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(20, $this->currency->value))],
                'public' => true,
            ])
        );

        $this->set = ProductSet::factory()->create();

        $this->discount->conditionGroups()->attach($this->conditionGroup);

        $this->discountService = App::make(DiscountServiceContract::class);
    }

    public function testCheckConditionGroupPass(): void
    {
        $this->prepareConditionGroup();

        $this->product->sets()->sync([$this->set->getKey()]);

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $this->assertTrue(
            $this->discountService->checkConditionGroup(
                $this->conditionGroup,
                $cart,
                Money::of(40.0, $this->currency->value),
            ),
        );
    }

    /**
     * @throws DtoException
     */
    public function testCheckConditionGroupFail(): void
    {
        $this->prepareConditionGroup();

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $this->product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $this->assertFalse(
            $this->discountService->checkConditionGroup(
                $this->conditionGroup,
                $cart,
                Money::of(40.0, $this->currency->value),
            ),
        );
    }

    /**
     * @throws UnknownCurrencyException
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function testCheckConditionGroupsPass(): void
    {
        $this->prepareConditionGroup();
        $this->discount->conditionGroups()->attach($this->prepareNewConditionGroup());

        $product = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(60, $this->currency->value))],
                'public' => true,
            ])
        );

        $product->sets()->sync([$this->set->getKey()]);

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $this->assertTrue(
            $this->discountService->checkConditionGroups(
                $this->discount,
                $cart,
                Money::of(120.0, $this->currency->value),
            ),
        );
    }

    /**
     * @throws UnknownCurrencyException
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function testCheckConditionGroupsFail(): void
    {
        $this->prepareConditionGroup();
        $this->discount->conditionGroups()->attach($this->prepareNewConditionGroup());

        $product = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(60, $this->currency->value))],
                'public' => true,
            ])
        );

        $cart = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
            ],
            'coupons' => [],
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $this->assertFalse(
            $this->discountService->checkConditionGroups(
                $this->discount,
                $cart,
                Money::of(120.0, $this->currency->value),
            ),
        );
    }

    private function prepareConditionGroup(): void
    {
        /** @var DiscountCondition $condition */
        $condition = DiscountCondition::query()->create([
            'type' => ConditionType::ORDER_VALUE,
            'condition_group_id' => $this->conditionGroup->getKey(),
            'value' => [
                'include_taxes' => false,
                'is_in_range' => true,
            ],
        ]);
        $condition->pricesMin()->create([
            'currency' => $this->currency->value,
            'value' => 999,
            'price_type' => DiscountConditionPriceType::PRICE_MIN->value,
        ]);
        $condition->pricesMax()->create([
            'currency' => $this->currency->value,
            'value' => 9999,
            'price_type' => DiscountConditionPriceType::PRICE_MAX->value,
        ]);

        $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN,
            'value' => [
                'products' => [
                    $this->product->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        $this->conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $this->set->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);
    }

    private function prepareNewConditionGroup(): ConditionGroup
    {
        /** @var ConditionGroup $conditionGroup */
        $conditionGroup = ConditionGroup::query()->create();

        /** @var DiscountCondition $condition */
        $condition = DiscountCondition::query()->create([
            'type' => ConditionType::ORDER_VALUE,
            'condition_group_id' => $conditionGroup->getKey(),
            'value' => [
                'include_taxes' => false,
                'is_in_range' => true,
            ],
        ]);
        $condition->pricesMin()->create([
            'currency' => $this->currency->value,
            'value' => 10000,
            'price_type' => DiscountConditionPriceType::PRICE_MIN->value,
        ]);
        $condition->pricesMax()->create([
            'currency' => $this->currency->value,
            'value' => 19999,
            'price_type' => DiscountConditionPriceType::PRICE_MAX->value,
        ]);

        $conditionGroup->conditions()->create([
            'type' => ConditionType::PRODUCT_IN_SET,
            'value' => [
                'product_sets' => [
                    $this->set->getKey(),
                ],
                'is_allow_list' => true,
            ],
        ]);

        return $conditionGroup;
    }
}
