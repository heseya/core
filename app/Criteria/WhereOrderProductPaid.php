<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereOrderProductPaid extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas('order', function (Builder $query): void {
            $query->where('paid', $this->value);
        });
    }
}
