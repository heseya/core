<?php

namespace App\Dtos;

use App\Enums\ConditionType;
use Exception;
use Heseya\Dto\Dto;

class ConditionGroupDto extends Dto
{
    protected array $conditions;

    public static function fromArray(array $array): self
    {
        return new self(
            conditions: self::transformArrayToConditionDtos($array)
        );
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    private static function transformArrayToConditionDtos(array $conditions): array
    {
        $result = [];
        foreach ($conditions as $condition) {
            array_push($result, match ($condition['type']) {
                ConditionType::ORDER_VALUE->value => OrderValueConditionDto::fromArray($condition),
                ConditionType::USER_IN_ROLE->value => UserInRoleConditionDto::fromArray($condition),
                ConditionType::USER_IN->value => UserInConditionDto::fromArray($condition),
                ConditionType::PRODUCT_IN_SET->value => ProductInSetConditionDto::fromArray($condition),
                ConditionType::PRODUCT_IN->value => ProductInConditionDto::fromArray($condition),
                ConditionType::DATE_BETWEEN->value => DateBetweenConditionDto::fromArray($condition),
                ConditionType::TIME_BETWEEN->value => TimeBetweenConditionDto::fromArray($condition),
                ConditionType::MAX_USES->value => MaxUsesConditionDto::fromArray($condition),
                ConditionType::MAX_USES_PER_USER->value => MaxUsesPerUserConditionDto::fromArray($condition),
                ConditionType::WEEKDAY_IN->value => WeekDayInConditionDto::fromArray($condition),
                ConditionType::CART_LENGTH->value => CartLengthConditionDto::fromArray($condition),
                ConditionType::COUPONS_COUNT->value => CouponsCountConditionDto::fromArray($condition),
                default => throw new Exception('Unknown condition type.')
            });
        }
        return $result;
    }
}
