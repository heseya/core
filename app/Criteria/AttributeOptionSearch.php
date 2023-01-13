<?php

namespace App\Criteria;

use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class AttributeOptionSearch extends Criterion
{
    public function query(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('id', 'LIKE', '%' . $this->value . '%')
                ->orWhere('name', 'LIKE', '%' . $this->value . '%')
                ->orWhere('value_number', 'LIKE', '%' . $this->value . '%')
                ->orWhere('value_date', 'LIKE', '%' . $this->value . '%');
        });
    }
}
