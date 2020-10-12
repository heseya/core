<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class ProductSearch extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('slug', 'LIKE', '%' . $this->value . '%')
            ->orWhere('name', 'LIKE', '%' . $this->value . '%')
            ->orWhereHas('brand', function (Builder $nestedQuery) {
                return $nestedQuery
                    ->where('name', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('slug', 'LIKE', '%' . $this->value . '%');
            })
            ->orWhereHas('category', function (Builder $nestedQuery) {
                return $nestedQuery
                    ->where('name', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('slug', 'LIKE', '%' . $this->value . '%');
            });
        });
    }
}
