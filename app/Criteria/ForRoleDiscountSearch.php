<?php

namespace App\Criteria;

use App\Enums\ConditionType;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class ForRoleDiscountSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas('conditionGroups', function (Builder $query): void {
            $query->whereHas('conditions', function (Builder $query): void {
                $query->where('type', ConditionType::USER_IN_ROLE)
                    ->where('value->is_allow_list', true)
                    ->where('value', 'LIKE', "%{$this->value}%");
            });
        });
    }
}
