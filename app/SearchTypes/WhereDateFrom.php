<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class WhereDateFrom extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->where(Str::beforeLast($this->key, '_from'), '>=', $this->value);
    }
}
