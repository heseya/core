<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class ProductSearch extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('slug', 'LIKE', '%' . $this->value . '%')
            ->orWhere('name', 'LIKE', '%' . $this->value . '%')
            ->orWhereHas('brand', function (Builder $query) {
                $query
                    ->where('name', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('slug', 'LIKE', '%' . $this->value . '%');
            })
            ->orWhereHas('category', function (Builder $query) {
                $query
                    ->where('name', 'LIKE', '%' . $this->value . '%')
                    ->orWhere('slug', 'LIKE', '%' . $this->value . '%');
            });
        });
    }
}
