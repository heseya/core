<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereBelongsToManyById extends Criterion
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
