<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereCreatedAfter extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where('created_at', '>=', $this->value);
    }
}
