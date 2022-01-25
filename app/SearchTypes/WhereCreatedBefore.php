<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class WhereCreatedBefore extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->where('created_at', '<=', $this->value);
    }
}
