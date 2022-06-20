<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereHasPhoto extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->has('media', $this->value ? '>' : '<=', 0);
    }
}
