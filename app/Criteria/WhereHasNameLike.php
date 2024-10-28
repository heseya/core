<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereHasNameLike extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where('name', 'LIKE', "%{$this->value}%");
    }
}
