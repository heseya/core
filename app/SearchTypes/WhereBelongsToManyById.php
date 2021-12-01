<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class WhereBelongsToManyById extends Search
{
    public function query(Builder $query): Builder
    {
        $query->whereHas($this->key, function (Builder $query) {
            if (is_array($this->value)) {
                return $query->whereIn($this->key . '.id', $this->value);
            }
            return $query->where($this->key . '.id', $this->value);
        });
        return $query;
    }
}
