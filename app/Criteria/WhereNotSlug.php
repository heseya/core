<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class WhereNotSlug extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas(Str::remove('_not', $this->key), function (Builder $query) {
            if (is_array($this->value)) {
                return $query->whereNotIn('slug', $this->value);
            }

            return $query->whereNot('slug', $this->value);
        });
    }
}
