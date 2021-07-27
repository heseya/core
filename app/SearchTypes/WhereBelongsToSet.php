<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class WhereBelongsToSet extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas('set', function (Builder $query) {
            if (is_array($this->value)) {
                return $query->whereIn('slug', $this->value);
            }

            return $query->where('slug', $this->value);
        });
    }
}
