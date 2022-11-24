<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereHasOrderWithCode extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas('shippingMethods', function (Builder $query): void {
            $query->whereHas('orders', function (Builder $query): void {
                $query->where('code', $this->value);
            });
        });
    }
}
