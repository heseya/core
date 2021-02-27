<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class WhereHasSlug extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas($this->key, function (Builder $query) {
            $query->where('slug', $this->value);
        });
    }
}
