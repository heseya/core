<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class DiscountSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('description', 'LIKE', '%' . $this->value . '%')
                ->orWhere('code', 'LIKE', '%' . $this->value . '%')
                ->orWhere('percentage', 'LIKE', '%' . $this->value . '%')
                ->orWhere('name', 'LIKE', '%' . $this->value . '%');
        });
    }
}
