<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class ProductSearch extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('id', 'LIKE', '%' . $this->value . '%')
                ->orWhere('slug', 'LIKE', '%' . $this->value . '%')
                ->orWhere('name', 'LIKE', '%' . $this->value . '%')
                ->orWhere('description_html', 'LIKE', '%' . $this->value . '%');
        });
    }
}
