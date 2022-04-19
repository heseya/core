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
                ConditionType::ORDER_VALUE => OrderValueConditionDto::fromArray($condition),
                ConditionType::USER_IN_ROLE => UserInRoleConditionDto::fromArray($condition),
                ConditionType::USER_IN => UserInConditionDto::fromArray($condition),
                ConditionType::PRODUCT_IN_SET => ProductInSetConditionDto::fromArray($condition),
                ConditionType::PRODUCT_IN => ProductInConditionDto::fromArray($condition),
                ConditionType::DATE_BETWEEN => DateBetweenConditionDto::fromArray($condition),
                ConditionType::TIME_BETWEEN => TimeBetweenConditionDto::fromArray($condition),
                ConditionType::MAX_USES => MaxUsesConditionDto::fromArray($condition),
                ConditionType::MAX_USES_PER_USER => MaxUsesPerUserConditionDto::fromArray($condition),
                ConditionType::WEEKDAY_IN => WeekDayInConditionDto::fromArray($condition),
                ConditionType::CART_LENGTH => CartLengthConditionDto::fromArray($condition),
                default => throw new Exception('Unknown condition type.')
            });
        }
        return $result;
    }
}
