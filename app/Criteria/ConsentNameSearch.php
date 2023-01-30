<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class ConsentNameSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->whereHas('consents', function (Builder $query): void {
            $query->where('name', 'LIKE', '%' . $this->value . '%');
        });
    }
}
