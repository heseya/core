<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class RoleAssignableSearch extends Search
{
    public function query(Builder $query): Builder
    {
        if (!$this->value) {
            return $query->whereIn('name', []);
        }

        return $query;
    }
}
