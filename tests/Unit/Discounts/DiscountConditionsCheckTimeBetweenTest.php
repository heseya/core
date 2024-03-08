<?php

namespace Tests\Unit\Discounts;

use App\Enums\ConditionType;
use App\Models\DiscountCondition;
use Brick\Money\Money;
use Illuminate\Support\Carbon;

class DiscountConditionsCheckTimeBetweenTest extends DiscountConditionsCheckCase
{
    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function timeBetweenPassProvider(): array
    {
        Carbon::setTestNow('2022-03-04T12:00:00');

        return [
            'in range' => [
                [
                    'start_at' => Carbon::now()->subHour()->toTimeString(),
                    'end_at' => Carbon::now()->addHour()->toTimeString(),
                    'is_in_range' => true,
                ],
            ],
            'not in range' => [
                [
                    'start_at' => Carbon::now()->addHour()->toTimeString(),
                    'end_at' => Carbon::now()->addHours(2)->toTimeString(),
                    'is_in_range' => false,
                ],
            ],
            'only start at in range' => [
                [
                    'start_at' => Carbon::now()->subHour()->toTimeString(),
                    'is_in_range' => true,
                ],
            ],
            'only start at not in range' => [
                [
                    'start_at' => Carbon::now()->addHour()->toTimeString(),
                    'is_in_range' => false,
                ],
            ],
            'only end at in range' => [
                [
                    'end_at' => Carbon::now()->addHour()->toTimeString(),
                    'is_in_range' => true,
                ],
            ],
            'only end at not in range' => [
                [
                    'end_at' => Carbon::now()->subHour()->toTimeString(),
                    'is_in_range' => false,
                ],
            ],
            'end at less in range' => [
                [
                    'start_at' => Carbon::now()->addHours(2)->toTimeString(),
                    'end_at' => Carbon::now()->addHour()->toTimeString(),
                    'is_in_range' => true,
                ],
            ],
            'end at less not in range' => [
                [
                    'start_at' => Carbon::now()->addHour()->toTimeString(),
                    'end_at' => Carbon::now()->subHour()->toTimeString(),
                    'is_in_range' => false,
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function timeBetweenFailProvider(): array
    {
        Carbon::setTestNow('2022-03-04T12:00:00');

        return [
            'in range' => [
                [
                    'start_at' => Carbon::now()->subHour()->toTimeString(),
                    'end_at' => Carbon::now()->addHour()->toTimeString(),
                    'is_in_range' => true,
                ],
            ],
            'not in range' => [
                [
                    'start_at' => Carbon::now()->addHour()->toTimeString(),
                    'end_at' => Carbon::now()->addHours(3)->toTimeString(),
                    'is_in_range' => false,
                ],
            ],
            'only start at in range' => [
                [
                    'start_at' => Carbon::now()->addHours(5)->toTimeString(),
                    'is_in_range' => true,
                ],
            ],
            'only start at not in range' => [
                [
                    'start_at' => Carbon::now()->subHour()->toTimeString(),
                    'is_in_range' => false,
                ],
            ],
            'only end at in range' => [
                [
                    'end_at' => Carbon::now()->subHour()->toTimeString(),
                    'is_in_range' => true,
                ],
            ],
            'only end at not in range' => [
                [
                    'end_at' => Carbon::now()->addHours(5)->toTimeString(),
                    'is_in_range' => false,
                ],
            ],
            'end at less in range' => [
                [
                    'start_at' => Carbon::now()->addHours(4)->toTimeString(),
                    'end_at' => Carbon::now()->addHour()->toTimeString(),
                    'is_in_range' => true,
                ],
            ],
            'end at less not in range' => [
                [
                    'start_at' => Carbon::now()->addHour()->toTimeString(),
                    'end_at' => Carbon::now()->subHour()->toTimeString(),
                    'is_in_range' => false,
                ],
            ],
        ];
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $value
     *
     * @dataProvider timeBetweenPassProvider
     */
    public function testCheckConditionTimeBetweenPass(array $value): void
    {
        Carbon::setTestNow('2022-03-04T12:00:00');

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::TIME_BETWEEN,
            'value' => $value,
        ]);

        $this->assertTrue(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $value
     *
     * @dataProvider timeBetweenFailProvider
     */
    public function testCheckConditionTimeBetweenFail(array $value): void
    {
        Carbon::setTestNow('2022-03-04T14:00:00');

        /** @var DiscountCondition $discountCondition */
        $discountCondition = $this->conditionGroup->conditions()->create([
            'type' => ConditionType::TIME_BETWEEN,
            'value' => $value,
        ]);

        $this->assertFalse(
            $this->discountService->checkCondition($discountCondition, Money::zero($this->currency->value))
        );
    }
}
