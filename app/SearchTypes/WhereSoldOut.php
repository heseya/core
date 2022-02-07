<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class WhereSoldOut extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->where('quantity', $this->value ? '<=' : '>', 0);
    }
}
