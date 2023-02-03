<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereHasSchemas extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->has('schemas', $this->value ? '>' : '<=', 0);
    }
}
