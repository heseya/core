<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereSoldOut extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where('quantity', $this->value ? '<=' : '>', 0);
    }
}
