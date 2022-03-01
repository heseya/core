<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class WhereInIds extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->whereIn('id', explode(',', $this->value));
    }
}
