<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class PageSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query
                ->where('name', 'LIKE', '%' . $this->value . '%')
                ->orWhere('slug', 'LIKE', '%' . $this->value . '%')
                ->orWhereRaw("JSON_SEARCH(name, 'one', ?) is not null", ['%' . $this->value . '%']);
        });
    }
}
