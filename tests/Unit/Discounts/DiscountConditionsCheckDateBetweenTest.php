<?php

namespace Tests\Unit\Discounts;

use App\Enums\ConditionType;
use App\Models\DiscountCondition;
use Brick\Money\Money;
use Illuminate\Support\Carbon;

class DiscountConditionsCheckDateBetweenTest extends DiscountConditionsCheckCase
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function dateBetweenPassProvider(): array
    {
        return [
            'in range' => [
                [
                    'start_at' => Carbon::create(2020, 02, 01, 10),
                    'end_at' => Carbon::create(2020, 02, 03, 10),
                    'is_in_range' => true,
                ],
            ],
            'not in range' => [
                [
                    'start_at' => Carbon::create(2020, 01, 01, 10),
                    'end_at' => Carbon::create(2020, 01, 20, 10),
                    'is_in_range' => false,
                ],
            ],
            'only start at in range' => [
                [
                    'start_at' => Carbon::create(2020, 02, 01, 10),
                    'is_in_range' => true,
                ],
            ],
            'only start at not in range' => [
                [
                    'start_at' => Carbon::create(2020, 02, 03, 10),
                    'is_in_range' => false,
                ],
            ],
            'only end at in range' => [
                [
                    'end_at' => Carbon::create(2020, 03, 01, 10),
                    'is_in_range' => true,
                ],
            ],
            'only end at not in range' => [
                [
                    'end_at' => Carbon::create(2020, 01, 01, 10),
                    'is_in_range' => false,
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function dateBetweenFailProvider(): array
    {
        return [
            'in range' => [
                [
                    'start_at' => Carbon::create(2020, 02, 03, 10),
                    'end_at' => Carbon::create(2020, 02, 04, 10),
                    'is_in_range' => true,
                ],
            ],
            'not in range' => [
                [
                    'start_at' => Carbon::create(2020, 02, 01, 10),
                    'end_at' => Carbon::create(2020, 02, 03, 10),
                    'is_in_range' => false,
                ],
            ],
        ];
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $value
     *
     * @dataProvider dateBetweenPassProvider
     */
    public function testCheckConditionDateBetweenPass(array $value): void
    {
        $this->travelTo(Carbon::create(2020, 02, 02, 10));

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => $value,
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $value
     *
     * @dataProvider dateBetweenFailProvider
     */
    public function testCheckConditionDateBetweenNotInRangePass(array $value): void
    {
        $this->travelTo(Carbon::create(2020, 02, 02, 10));

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::DATE_BETWEEN,
            'value' => $value,
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }
}
