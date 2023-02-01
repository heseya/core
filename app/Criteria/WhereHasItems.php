<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereHasItems extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->has('items', $this->value ? '>' : '<=', 0);
    }
}
