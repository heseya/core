<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class ProductSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereFullText([
            'name',
            'slug',
            'description_html',
            'description_short',
            'search_values',
        ], $this->value);
    }
}
