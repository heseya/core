<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class RoleSearch extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('name', 'LIKE', '%' . $this->value . '%')
                ->orWhere('description', 'LIKE', '%' . $this->value . '%');
        });
    }
}