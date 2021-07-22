<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class ProductSetSearch extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('name', 'LIKE', '%' . $this->value . '%')
                ->orWhere('slug', 'LIKE', '%' . $this->value . '%')
                ->orWhereHas('parent', function (Builder $query) {
                    $query
                        ->where('name', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('slug', 'LIKE', '%' . $this->value . '%');
                })
                ->orWhereHas('children', function (Builder $query) {
                    $query
                        ->where('name', 'LIKE', '%' . $this->value . '%')
                        ->orWhere('slug', 'LIKE', '%' . $this->value . '%');
                });
        });
    }
}
