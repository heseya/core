<?php

namespace App\SearchTypes;

use Heseya\Searchable\Searches\Search;
use Illuminate\Database\Eloquent\Builder;

class DiscountSearch extends Search
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query): Builder {
            return $query->where('name', '%' . $this->value . '%')
                ->orWhere('code', '%' . $this->value . '%')
                ->orWhere('type', '%' . $this->value . '%')
                ->orWhere('discount', '%' . $this->value . '%');
        });
    }
}
