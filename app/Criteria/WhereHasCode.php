<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereHasCode extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where('code', $this->value ? '!=' : '=', null);
    }
}
