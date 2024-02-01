<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class AttributeProductSetsCriteria extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where(
            fn ($query) => $query
                ->whereHas(
                    'productSets',
                    fn ($query) => $query->whereIn('product_set_id', $this->value),
                )
                ->orWhere('global', '=', true),
        );
    }
}
