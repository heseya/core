<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereInIds extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereIn('id', $this->value);
    }
}
