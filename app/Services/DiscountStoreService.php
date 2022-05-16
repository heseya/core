<?php

namespace App\Services;

use App\Dtos\ConditionGroupDto;
use App\Services\Contracts\DiscountStoreServiceContract;
use Heseya\Dto\Missing;

class DiscountStoreService implements DiscountStoreServiceContract
{
    public function mapConditionGroups(array|Missing $conditionGroups): array|Missing
    {
        if ($conditionGroups instanceof Missing) {
            return $conditionGroups;
        }

        $conditionGroupDtos = [];

        foreach ($conditionGroups as $conditionGroup) {
            array_push(
                $conditionGroupDtos,
                ConditionGroupDto::fromArray($conditionGroup['conditions']),
            );
        }

        return $conditionGroupDtos;
    }
}
