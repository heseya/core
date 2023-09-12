<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class OrderPayments extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas('payments', function (Builder $query): void {
            $query->where('method_id', $this->value);
        });
    }
}
