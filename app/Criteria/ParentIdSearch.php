<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class ParentIdSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        $searchValue = $this->value !== '' ? $this->value : null;

        $searchValue === null ?
            $query->whereNull('parent_id') :
            $query->where('parent_id', 'LIKE', '%' . $searchValue . '%');

        return $query;
    }
}
