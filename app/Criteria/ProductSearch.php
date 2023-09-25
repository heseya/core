<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class ProductSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereFullText([
            'products.name',
            'products.description_html',
            'products.description_short',
            'products.search_values',
        ], $this->value);
    }
}
