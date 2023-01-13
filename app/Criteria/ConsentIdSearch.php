<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class ConsentIdSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas('consents', function (Builder $query): void {
            $query->where('value', '=', 1)
                ->where('id', 'LIKE', $this->value);
        });
    }
}
