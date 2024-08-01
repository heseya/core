<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class SchemaHasProduct extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $this->value
            ? $query->whereNotNull('product_id')
            : $query->whereNull('product_id');
    }
}
