<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class MoreOrEquals extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where($this->key, '>=', $this->value);
    }
}
