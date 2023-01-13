<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class DepositSkuSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas('item', function (Builder $query): void {
            $query->where('sku', $this->value);
        });
    }
}
