<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereUpdatedAfter extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where('updated_at', '>=', $this->value);
    }
}
