<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class SchemaSearch extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('name', 'LIKE', '%' . $this->value . '%')
                ->orWhere('description', 'LIKE', '%' . $this->value . '%');
        });
    }
}