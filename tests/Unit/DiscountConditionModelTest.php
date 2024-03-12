<?php

namespace Tests\Unit;

use App\Enums\ConditionType;
use App\Models\ConditionGroup;
use App\Models\DiscountCondition;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\DiscountConditionPriceType;
use Domain\ProductSet\ProductSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DiscountConditionModelTest extends TestCase
{
    use RefreshDatabase;

    public static function conditionsProvider(): array
    {
        return [
            ConditionType::DATE_BETWEEN->value => [
                ConditionType::DATE_BETWEEN,
                [
                    'start_at' => Carbon::yesterday()->toISOString(),
                    'end_at' => Carbon::tomorrow()->toISOString(),
                    'is_in_range' => false,
                ],
            ],
            ConditionType::TIME_BETWEEN->value => [
                ConditionType::TIME_BETWEEN,
                [
                    'start_at' => Carbon::now()->subHour()->toTimeString(),
                    'end_at' => Carbon::now()->addHour()->toTimeString(),
                    'is_in_range' => false,
                ],
            ],
            ConditionType::MAX_USES->value => [
                ConditionType::MAX_USES,
                [
                    'max_uses' => 100,
                ],
            ],
            ConditionType::MAX_USES_PER_USER->value => [
                ConditionType::MAX_USES_PER_USER,
                [
                    'max_uses' => 100,
                ],
            ],
            ConditionType::WEEKDAY_IN->value => [
                ConditionType::WEEKDAY_IN,
                [
                    'weekday' => [true, false, false, true, true, false, false],
                ],
            ],
        ];
    }

    /**
     * @dataProvider conditionsProvider
     */
    public function testCastValue($type, $value): void
    {
        $conditionGroup = ConditionGroup::create();

        $condition = DiscountCondition::create([
            'condition_group_id' => $conditionGroup->getKey(),
            'type' => $type,
            'value' => $value,
        ]);

        $this->assertEquals($condition->value, $value);
    }

    public function testCastValueOrderValue(): void
    {
        $conditionGroup = ConditionGroup::create();

        $value = [
            'min_values' => [
                [
                    'value' => "20.00",
                    'currency' => Currency::DEFAULT->value,
                ]
            ],
            'max_values' => [
                [
                    'value' => "100.00",
                    'currency' => Currency::DEFAULT->value,
                ]
            ],
            'include_taxes' => false,
            'is_in_range' => false,
        ];

        $type = ConditionType::ORDER_VALUE;

        /** @var DiscountCondition $condition */
        $condition = DiscountCondition::create([
            'condition_group_id' => $conditionGroup->getKey(),
            'type' => $type,
            'value' => $value,
        ]);
        $condition->pricesMin()->create([
            'value' => 2000,
            'currency' => Currency::DEFAULT->value,
            'price_type' => DiscountConditionPriceType::PRICE_MIN->value,
        ]);
        $condition->pricesMin()->create([
            'value' => 10000,
            'currency' => Currency::DEFAULT->value,
            'price_type' => DiscountConditionPriceType::PRICE_MAX->value,
        ]);

        $value['min_values'] = array_map(fn (array $price) => PriceDto::from($price), $value['min_values']);
        $value['max_values'] = array_map(fn (array $price) => PriceDto::from($price), $value['max_values']);

        $this->assertEquals($condition->value, $value);
    }

    public function testCastValueUserInRole(): void
    {
        $conditionGroup = ConditionGroup::create();

        $role = Role::factory()->create();
        $value = [
            'roles' => [$role->getKey()],
            'is_allow_list' => false,
        ];
        $type = ConditionType::USER_IN_ROLE;

        $condition = DiscountCondition::create([
            'condition_group_id' => $conditionGroup->getKey(),
            'type' => $type,
            'value' => $value,
        ]);

        $this->assertEquals($condition->value, $value);
    }

    public function testCastValueUserIn(): void
    {
        $conditionGroup = ConditionGroup::create();

        $user = User::factory()->create();
        $value = [
            'users' => [$user->getKey()],
            'is_allow_list' => false,
        ];
        $type = ConditionType::USER_IN;

        $condition = DiscountCondition::create([
            'condition_group_id' => $conditionGroup->getKey(),
            'type' => $type,
            'value' => $value,
        ]);

        $this->assertEquals($condition->value, $value);
    }

    public function testCastValueProductInSet(): void
    {
        $conditionGroup = ConditionGroup::create();

        $productSet = ProductSet::factory()->create();
        $value = [
            'product_sets' => [$productSet->getKey()],
            'is_allow_list' => false,
        ];
        $type = ConditionType::PRODUCT_IN_SET;

        $condition = DiscountCondition::create([
            'condition_group_id' => $conditionGroup->getKey(),
            'type' => $type,
            'value' => $value,
        ]);

        $this->assertEquals($condition->value, $value);
    }

    public function testCastValueProductIn(): void
    {
        $conditionGroup = ConditionGroup::create();

        $product = Product::factory()->create();
        $value = [
            'products' => [$product->getKey()],
            'is_allow_list' => false,
        ];
        $type = ConditionType::PRODUCT_IN;

        $condition = DiscountCondition::create([
            'condition_group_id' => $conditionGroup->getKey(),
            'type' => $type,
            'value' => $value,
        ]);

        $this->assertEquals($condition->value, $value);
    }
}
