<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class DiscountSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query): Builder {
            return $query->where('description', '%' . $this->value . '%')
                ->orWhere('code', '%' . $this->value . '%')
                ->orWhere('type', '%' . $this->value . '%')
                ->orWhere('discount', '%' . $this->value . '%');
        });
    }
}
