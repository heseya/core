<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereBelongsToSet extends Criterion
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
